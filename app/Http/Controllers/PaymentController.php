<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Models\CustomerDetail;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Menampilkan daftar semua pembayaran.
     */
    public function index()
    {
        $payments = Payment::with(['customerDetails', 'cart'])->get();

        return response()->json([
            'message' => 'Daftar pembayaran',
            'data'    => $payments
        ], 200);
    }

    /**
     * Menampilkan detail pembayaran tertentu.
     */
    public function show($id)
    {
        $payment = Payment::with(['customerDetails', 'cart'])->findOrFail($id);

        return response()->json([
            'message' => 'Detail pembayaran',
            'data'    => $payment
        ], 200);
    }

    /**
     * Membuat transaksi baru berdasarkan Cart.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'  => [
                'required',
                'string',
                Rule::exists('customer_details', 'user_id')
            ],
            'cart_id'  => [
                'required',
                'string',
                Rule::exists('carts', 'id')
            ],
            'email'    => 'required|email'
        ]);

        Log::info('Request data:', $request->all());
        Log::info('Validated data:', $validated);

        // Ambil data cart
        $cart = Cart::with('customerDetails')->findOrFail($validated['cart_id']);

        // Verifikasi bahwa cart dimiliki oleh customer yang benar
        if ($cart->user_id !== $validated['user_id']) {
            return response()->json(['message' => 'Cart tidak dimiliki oleh customer ini'], 403);
        }

        $orderId = 'ORDER-' . Str::random(10);
        $customerName = $cart->customer_details->name ?? 'Unknown';

        DB::beginTransaction();
        try {
            // Simpan data pembayaran ke database
            $payment = Payment::create([
                'user_id'        => $validated['user_id'],
                'name'           => $customerName,
                'cart_id'        => $validated['cart_id'],
                'total_price'    => $cart->total,
                'payment_status' => 'pending',
                'order_id'       => $orderId,
                'expiry_time'    => now()->addDay(), // Set expiry 24 jam
            ]);

            // Persiapan data item untuk Midtrans
            $itemDetails = [];
            foreach ($cart->items as $item) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $itemDetails[] = [
                        'id'       => $product->id,
                        'price'    => $product->price,
                        'quantity' => $item['quantity'],
                        'name'     => $product->name,
                    ];
                }
            }

            // Tambahkan pajak dan diskon jika ada
            if ($cart->tax > 0) {
                $itemDetails[] = [
                    'id'    => 'TAX',
                    'price' => $cart->tax,
                    'quantity' => 1,
                    'name'  => 'Tax',
                ];
            }
            if ($cart->discount > 0) {
                $itemDetails[] = [
                    'id'    => 'DISCOUNT',
                    'price' => -$cart->discount,
                    'quantity' => 1,
                    'name'  => 'Discount',
                ];
            }

            // Data transaksi untuk Midtrans
            $transactionDetails = [
                'order_id'     => $orderId,
                'gross_amount' => (int)$cart->total, // Midtrans membutuhkan integer
            ];

            $customerDetails = [
                'first_name' => $customerName,
                'email'      => $request->input('email', 'customer@mail.com'),
                'phone'      => $cart->customer_details->phone ?? '',
            ];

            $transaction = [
                'transaction_details' => $transactionDetails,
                'customerDetails'    => $customerDetails,
                'item_details'        => $itemDetails,
                'expiry' => [
                    'unit'     => 'day',
                    'duration' => 1,
                ],
            ];

            // Buat Snap Token dari Midtrans
            $snapToken = Snap::getSnapToken($transaction);

            // Simpan token dan URL pembayaran dari Midtrans
            $payment->midtrans_token = $snapToken;
            $payment->midtrans_url = "https://app." . (Config::$isProduction ? "" : "sandbox.") . "midtrans.com/snap/v2/vtweb/" . $snapToken;
            $payment->save();

            DB::commit();

            return response()->json([
                'message' => 'Pembayaran berhasil dibuat',
                'data'    => $payment
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Payment creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error saat memproses pembayaran: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update status pembayaran secara manual (untuk admin).
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:pending,settlement,expire,cancel,deny,refund,chargeback',
        ]);

        DB::beginTransaction();
        try {
            $payment = Payment::findOrFail($id);
            $payment->payment_status = $validated['payment_status'];

            // Jika status berubah menjadi settlement, update stok produk
            if ($validated['payment_status'] === 'settlement' && $payment->payment_status !== 'settlement') {
                $this->reduceProductStock($payment->cart_id);
                $payment->settlement_time = now();
            }

            $payment->save();
            DB::commit();

            return response()->json([
                'message' => 'Status pembayaran berhasil diperbarui',
                'data'    => $payment
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment update failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error saat memperbarui pembayaran: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Menangani notifikasi dari Midtrans.
     */
    public function handleNotification(Request $request)
    {
        // Log the entire raw payload
        Log::info('Raw Midtrans Notification', ['payload' => $request->getContent()]);

        try {
            // Parse the notification body
            $notificationBody = json_decode($request->getContent(), true);

            // Validate essential parameters
            $orderId = $notificationBody['order_id'] ?? null;
            $statusCode = $notificationBody['status_code'] ?? null;
            $grossAmount = $notificationBody['gross_amount'] ?? null;
            $signatureKey = $notificationBody['signature_key'] ?? null;

            // Validate required parameters
            if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
                Log::warning('Incomplete Midtrans notification', $notificationBody);
                return response()->json(['message' => 'Invalid notification data'], 400);
            }

            // Get server key from config
            $serverKey = config('midtrans.server_key');

            // Generate signature for verification
            $generatedSignature = hash(
                'sha512',
                $orderId .
                    $statusCode .
                    $grossAmount .
                    $serverKey
            );

            // Log signature verification details
            Log::info('Signature Verification', [
                'received_signature' => $signatureKey,
                'generated_signature' => $generatedSignature,
                'match' => $generatedSignature === $signatureKey
            ]);

            // Verify signature
            if ($generatedSignature !== $signatureKey) {
                Log::warning('Invalid Midtrans signature', [
                    'order_id' => $orderId,
                    'received_signature' => $signatureKey,
                    'generated_signature' => $generatedSignature
                ]);
                return response()->json(['message' => 'Invalid signature'], 403);
            }

            // Find the payment
            $payment = Payment::where('order_id', $orderId)->first();
            if (!$payment) {
                Log::warning('Payment not found', ['order_id' => $orderId]);
                return response()->json(['message' => 'Payment not found'], 404);
            }

            // Begin database transaction
            DB::beginTransaction();

            // Update payment status
            $payment->payment_status = $notificationBody['transaction_status'] ?? 'pending';
            $payment->payment_type = $notificationBody['payment_type'] ?? null;

            // Handle successful payment
            $transactionStatus = $notificationBody['transaction_status'] ?? null;
            $fraudStatus = $notificationBody['fraud_status'] ?? null;

            if (
                $transactionStatus === 'settlement' ||
                ($transactionStatus === 'capture' && $fraudStatus === 'accept')
            ) {
                $payment->settlement_time = now();
                $this->reduceProductStock($payment->cart_id);
            }

            $payment->save();
            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Notification processed'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Midtrans notification processing failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process notification'
            ], 500);
        }
    }

    /**
     * Mendapatkan status pembayaran saat ini.
     */
    public function getStatus($orderId)
    {
        $payment = Payment::where('order_id', $orderId)->firstOrFail();

        return response()->json([
            'message' => 'Status pembayaran',
            'data'    => [
                'order_id'       => $payment->order_id,
                'total_price'    => $payment->total_price,
                'payment_status' => $payment->payment_status,
                'payment_type'   => $payment->payment_type,
                'midtrans_url'   => $payment->midtrans_url,
                'expiry_time'    => $payment->expiry_time,
            ]
        ], 200);
    }

    /**
     * Helper method untuk mengurangi stok produk.
     */
    private function reduceProductStock($cartId)
    {
        $cart = Cart::find($cartId);
        if ($cart && $cart->items) {
            foreach ($cart->items as $item) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $product->stock -= $item['quantity'];
                    $product->save();
                }
            }
        }
    }

}
