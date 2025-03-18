<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Customer;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $payments = Payment::with(['customer', 'cart'])->get();

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
        $payment = Payment::with(['customer', 'cart'])->findOrFail($id);

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
            'cs_id'    => 'required|uuid|exists:customers,id',
            'cart_id'  => 'required|exists:carts,id',
            'cs_email'    => 'required|email'
        ]);

        // Ambil data cart
        $cart = Cart::with('customer')->findOrFail($validated['cart_id']);
        if (!$cart->items || empty($cart->items)) {
            return response()->json(['message' => 'Keranjang kosong'], 400);
        }

        // Verifikasi bahwa cart dimiliki oleh customer yang benar
        if ($cart->cs_id !== $validated['cs_id']) {
            return response()->json(['message' => 'Cart tidak dimiliki oleh customer ini'], 403);
        }

        $orderId = 'ORDER-' . Str::random(10);
        $customerName = $cart->customer->cs_name ?? 'Unknown';

        DB::beginTransaction();
        try {
            // Simpan data pembayaran ke database
            $payment = Payment::create([
                'cs_id'          => $validated['cs_id'],
                'cs_name'        => $customerName,
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
                'cs_email'      => $request->input('email', 'customer@mail.com'),
                'phone'      => $cart->customer->cs_phone ?? '',
            ];

            $transaction = [
                'transaction_details' => $transactionDetails,
                'customer_details'    => $customerDetails,
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
        $notificationBody = json_decode($request->getContent(), true);
        Log::info('Midtrans notification received', $notificationBody);

        $orderId = $notificationBody['order_id'] ?? null;
        $transactionStatus = $notificationBody['transaction_status'] ?? null;
        $statusCode = $notificationBody['status_code'] ?? null;
        $settlementTime = $notificationBody['settlement_time'] ?? null;
        $paymentType = $notificationBody['payment_type'] ?? null;
        $fraudStatus = $notificationBody['fraud_status'] ?? null;
        $grossAmount = $notificationBody['gross_amount'] ?? null;

        if (!$orderId || !$transactionStatus) {
            return response()->json(['message' => 'Data notifikasi tidak valid'], 400);
        }

        // Ambil data payment berdasarkan order_id
        $payment = Payment::where('order_id', $orderId)->first();
        if (!$payment) {
            return response()->json(['message' => 'Pembayaran tidak ditemukan'], 404);
        }

        // Verifikasi signature key sebenarnya dilakukan di middleware, tetapi ini contoh implementasi
        // Pada implementasi sebenarnya, sebaiknya gunakan Midtrans\Notification untuk validasi

        DB::beginTransaction();
        try {
            // Perbarui status pembayaran berdasarkan transaction_status
            $payment->payment_status = $transactionStatus;
            $payment->payment_type = $paymentType;

            // Jika pembayaran sukses, kurangi stok produk
            if (
                $transactionStatus === 'settlement' ||
                ($transactionStatus === 'capture' && $fraudStatus === 'accept')
            ) {
                $payment->settlement_time = $settlementTime ?? now();
                $this->reduceProductStock($payment->cart_id);
            }

            $payment->save();
            DB::commit();

            return response()->json(['message' => 'Status pembayaran diperbarui'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment update failed: ' . $e->getMessage());
            return response()->json(['message' => 'Error saat memperbarui pembayaran: ' . $e->getMessage()], 500);
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
