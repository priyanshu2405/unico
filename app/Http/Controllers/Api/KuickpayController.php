<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class KuickpayController extends Controller
{
    private $username;
    private $password;

    public function __construct()
    {
        $this->username = config('services.kuickpay.username', 'Kuickpaytest');
        $this->password = config('services.kuickpay.password', 'Kuickpay@test12');
    }

    private function validateAuth(Request $request)
    {
        // In local/development environment, we bypass the credentials check to support the 
        // Kuickpay sandbox simulator, which often sends empty/blank credentials.
        if (config('app.env') === 'local') {
            return true;
        }

        // Support standard HTTP Basic Auth headers ($request->getUser() / $request->getPassword())
        // as well as custom header/input parameters.
        $user = $request->getUser() ?? $request->header('username') ?? $request->input('username');
        $pass = $request->getPassword() ?? $request->header('password') ?? $request->input('password');

        if ($user !== $this->username || $pass !== $this->password) {
            return false;
        }
        return true;
    }

    public function billInquiry(Request $request)
    {
        Log::info('Kuickpay BillInquiry Request:', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => collect($request->headers->all())->map(fn($v) => $v[0] ?? $v)->toArray(),
            'body' => $request->all(),
            'basic_user' => $request->getUser(),
            'basic_password' => $request->getPassword()
        ]);

        if ($request->has('tran_auth_id') || $request->has('transaction_amount')) {
            Log::info('Kuickpay BillInquiry: Redirecting payment request containing tran_auth_id to billPayment');
            return $this->billPayment($request);
        }

        if (!$this->validateAuth($request)) {
            return $this->jsonResponse([
                'response_Code' => '04',
                'response_description' => 'Invalid data — wrong credentials or malformed request.'
            ], 401, ['WWW-Authenticate' => 'Basic realm="Kuickpay API"']);
        }

        $consumerNumber = $request->input('consumer_number');
        if (empty($consumerNumber)) {
            return $this->jsonResponse([
                'response_Code' => '04',
                'response_description' => 'Consumer number is required.'
            ]);
        }

        $order = Order::where('kuickpay_consumer_number', $consumerNumber)->first();

        if (!$order) {
            return $this->jsonResponse([
                'response_Code' => '01',
                'response_description' => 'Consumer number not found.'
            ]);
        }

        // Return Code 02 if order is cancelled
        if ($order->status === 'cancelled') {
            return $this->jsonResponse([
                'response_Code' => '02',
                'response_description' => 'Voucher is blocked or inactive.'
            ]);
        }

        $status = $order->status === 'completed' ? 'P' : 'U';
        $dueDate = Carbon::parse($order->created_at)->addDays(7)->format('Ymd');
        $billingMonth = Carbon::parse($order->created_at)->format('y') . Carbon::parse($order->created_at)->format('m');
        
        // Formatting amount: In decimal string PKR (e.g. "250.00")
        $amountVal = number_format((float) $order->amount, 2, '.', '');

        $response = [
            'response_Code' => '00',
            'response_description' => 'Bill Found',
            'consumer_detail' => strtoupper(substr(preg_replace('/[^A-Za-z0-9 ]/', '', $order->user->name ?? 'CUSTOMER'), 0, 50)),
            'bill_status' => $status,
            'due_date' => $dueDate,
            'amount_within_due_date' => $amountVal,
            'amount_after_due_date' => $amountVal,
            'billing_month' => $billingMonth,
            'date_paid' => $order->status === 'completed' ? Carbon::parse($order->updated_at)->format('Ymd') : '',
            'amount_paid' => $order->status === 'completed' ? $amountVal : '',
            'tran_auth_id' => $order->status === 'completed' ? substr($order->transaction_id, 0, 20) : '',
            'contact_number' => substr($order->user->phone ?? '03000000000', 0, 15),
            'email' => substr($order->user->email ?? 'customer@example.com', 0, 100),
            'reserved' => (string) $request->input('reserved', ''),
            'institution_id' => (string) config('services.kuickpay.prefix', '01520'),
            'institution_name' => 'UNICOU'
        ];

        Log::info('Kuickpay BillInquiry Response:', $response);
        return $this->jsonResponse($response);
    }

    public function billPayment(Request $request)
    {
        Log::info('Kuickpay BillPayment Request:', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => collect($request->headers->all())->map(fn($v) => $v[0] ?? $v)->toArray(),
            'body' => $request->all(),
            'basic_user' => $request->getUser(),
            'basic_password' => $request->getPassword()
        ]);


        if (!$this->validateAuth($request)) {
            return $this->jsonResponse([
                'response_Code' => '04',
                'response_description' => 'Invalid data — missing/wrong fields or bad credentials.'
            ], 401, ['WWW-Authenticate' => 'Basic realm="Kuickpay API"']);
        }

        $consumerNumber = $request->input('consumer_number');
        $tranAuthId = $request->input('tran_auth_id');
        $paidAmount = $request->input('transaction_amount') ?? $request->input('paid_amount');
        $tranDate = $request->input('tran_date');
        $tranTime = $request->input('tran_time');
        
        if (empty($consumerNumber) || empty($tranAuthId) || $paidAmount === null || empty($tranDate)) {
            return $this->jsonResponse([
                'response_Code' => '04',
                'response_description' => 'Invalid data — missing mandatory fields.'
            ]);
        }

        // 1. Check if tran_auth_id has already been processed globally (Idempotency check)
        $existingOrderWithTxn = Order::where('transaction_id', $tranAuthId)->first();
        if ($existingOrderWithTxn) {
            return $this->jsonResponse([
                'response_Code' => '03',
                'response_description' => 'Already paid — duplicate tran_auth_id received.',
                'Identification_parameter' => $existingOrderWithTxn->user->email ?? 'success',
                'reserved' => ''
            ]);
        }

        $order = Order::where('kuickpay_consumer_number', $consumerNumber)->first();

        if (!$order) {
            return $this->jsonResponse([
                'response_Code' => '01',
                'response_description' => 'Invalid Voucher Number.'
            ]); 
        }

        // Return Code 02 if order is cancelled/blocked
        if ($order->status === 'cancelled') {
            return $this->jsonResponse([
                'response_Code' => '02',
                'response_description' => 'Voucher is blocked. Payment not allowed.'
            ]);
        }

        // 2. If the bill is already paid, validate all four fields (Consumer number, Date paid, tran_auth_id, Amount paid)
        $expectedAmountMajor = (float) $order->amount;
        $expectedAmountMinor = (float) ($order->amount * 100);
        $paidAmountVal = (float) $paidAmount;

        if ($order->status === 'completed' || $order->status === 'delivered') {
            $orderPaidDate = Carbon::parse($order->updated_at)->format('Ymd');

            if ($order->kuickpay_consumer_number === $consumerNumber &&
                $orderPaidDate === $tranDate &&
                $order->transaction_id === $tranAuthId &&
                (abs($paidAmountVal - $expectedAmountMajor) < 0.01 || abs($paidAmountVal - $expectedAmountMinor) < 0.01)) {
                return $this->jsonResponse([
                    'response_Code' => '03',
                    'response_description' => 'Already paid — duplicate tran_auth_id received.',
                    'Identification_parameter' => $order->user->email ?? 'success',
                    'reserved' => ''
                ]);
            }

            return $this->jsonResponse([
                'response_Code' => '03',
                'response_description' => 'This bill has already been paid.',
                'Identification_parameter' => $order->user->email ?? 'success',
                'reserved' => ''
            ]);
        }

        // Validate amount (support both PKR and Paisa)
        if (abs($paidAmountVal - $expectedAmountMajor) > 0.01 && abs($paidAmountVal - $expectedAmountMinor) > 0.01) {
            Log::warning("Kuickpay Amount Mismatch: Expected $expectedAmountMajor (PKR) or $expectedAmountMinor (paisa), Got $paidAmount");
            if (config('app.env') !== 'local') {
                return $this->jsonResponse([
                    'response_Code' => '02',
                    'response_description' => 'Amount Mismatch'
                ]);
            }
        }

        // Mark the bill as paid in DB
        $order->status = 'completed';
        $order->transaction_id = $tranAuthId;
        
        // Save transferred amount correctly (assumes PKR)
        if (abs($paidAmountVal - $expectedAmountMinor) < 0.01) {
            $order->amount_transferred = $paidAmountVal / 100;
        } elseif (abs($paidAmountVal - $expectedAmountMajor) < 0.01) {
            $order->amount_transferred = $paidAmountVal;
        } else {
            $order->amount_transferred = $expectedAmountMajor;
        }
        $order->save();

        // Handle Wallet Top-up
        if ($order->voucher_type === 'wallet_topup') {
            $user = $order->user;
            if ($user) {
                $conversion = \App\Services\CurrencyService::convertToBase((float)$order->amount, $order->currency ?: 'PKR');
                $amountInUSD = (float)$conversion['base_amount'];

                $user->wallet_balance += $amountInUSD;
                $user->withdrawable_balance += $amountInUSD;
                $user->save();

                \App\Models\WalletLedger::create([
                    'transaction_id' => $tranAuthId,
                    'user_id' => $user->id,
                    'type' => 'credit',
                    'amount' => $amountInUSD,
                    'source' => 'kuickpay',
                    'description' => "Wallet Top-up via KuickPay ({$order->amount} PKR)",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'created_at' => now(),
                ]);
            }
        } else {

            // ✅ AUTO DELIVERY FOR VOUCHERS
            try {
                $inventory = $order->inventoryVoucher;
                if ($inventory && $inventory->upload_vouchers) {
                    $available = array_diff(
                        $inventory->upload_vouchers,
                        $inventory->delivered_vouchers ?? []
                    );

                    if (count($available) >= $order->quantity) {
                        $codes = array_slice($available, 0, $order->quantity);
                        $codesString = implode("\n", $codes);

                        // Call processDelivery from VoucherController
                        $voucherController = new \App\Http\Controllers\Dashboard\VoucherController();
                        $voucherController->processDelivery($order, $codesString);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Kuickpay Auto-delivery Failed for Order #{$order->order_id}: " . $e->getMessage());
            }
        }

        $response = [
            'response_Code' => '00',
            'response_description' => 'Payment Successful',
            'Identification_parameter' => $order->user->email ?? 'success',
            'reserved' => (string) $request->input('reserved', '')
        ];

        Log::info('Kuickpay BillPayment Response:', $response);
        return $this->jsonResponse($response);
    }

    private function jsonResponse(array $data, int $status = 200, array $headers = [])
    {
        if (isset($data['response_Code'])) {
            $data['response_code'] = $data['response_Code'];
            $data['responseCode'] = $data['response_Code'];
        } elseif (isset($data['response_code'])) {
            $data['response_Code'] = $data['response_code'];
            $data['responseCode'] = $data['response_code'];
        }
        
        if (isset($data['response_description'])) {
            $data['response_desc'] = $data['response_description'];
        } elseif (isset($data['response_desc'])) {
            $data['response_description'] = $data['response_desc'];
        }

        // Casing compatibility for other fields
        $keysToMap = [
            'consumer_detail' => ['consumer_detail', 'consumerDetail', 'ConsumerDetail', 'consumer_Detail', 'Consumer_Detail'],
            'bill_status' => ['bill_status', 'billStatus', 'BillStatus', 'bill_Status', 'Bill_Status'],
            'due_date' => ['due_date', 'dueDate', 'DueDate', 'due_Date', 'Due_Date'],
            'amount_within_due_date' => ['amount_within_due_date', 'amountWithinDueDate', 'AmountWithinDueDate', 'amount_Within_Due_Date', 'Amount_Within_Due_Date', 'amount_within_due', 'amountWithinDue', 'AmountWithinDue', 'amount_Within_Due', 'Amount_Within_Due', 'amount_due', 'amountDue', 'AmountDue', 'total_payable', 'totalPayable', 'TotalPayable', 'amount_within_duedate', 'amountWithinDuedate', 'AmountWithinDuedate', 'amount_payable', 'amountPayable', 'AmountPayable', 'amount_payable_within_due_date', 'amountPayableWithinDueDate', 'AmountPayableWithinDueDate', 'payable_amount', 'payableAmount', 'PayableAmount', 'total_amount', 'totalAmount', 'TotalAmount', 'net_amount', 'netAmount', 'NetAmount', 'gross_amount', 'grossAmount', 'GrossAmount', 'amount_with_due_date', 'amountWithDueDate', 'AmountWithDueDate', 'amount_with_due', 'amountWithDue', 'AmountWithDue'],
            'amount_after_due_date' => ['amount_after_due_date', 'amountAfterDueDate', 'AmountAfterDueDate', 'amount_After_Due_Date', 'Amount_After_Due_Date', 'amount_after_due', 'amountAfterDue', 'AmountAfterDue', 'amount_After_Due', 'Amount_After_Due', 'amount_after_duedate', 'amountAfterDuedate', 'AmountAfterDuedate', 'amount_payable_after_due_date', 'amountPayableAfterDueDate', 'AmountPayableAfterDueDate', 'amount_after_due_date', 'amountAfterDueDate', 'AmountAfterDueDate', 'amount_after_due', 'amountAfterDue', 'AmountAfterDue'],
            'amount_within_due' => ['amount_within_due', 'amountWithinDue', 'AmountWithinDue', 'amount_Within_Due', 'Amount_Within_Due', 'amount_within_due_date', 'amountWithinDueDate', 'AmountWithinDueDate', 'amount_Within_Due_Date', 'Amount_Within_Due_Date', 'amount_within_duedate', 'amountWithinDuedate', 'AmountWithinDuedate', 'amount_payable_within_due_date', 'amountPayableWithinDueDate', 'AmountPayableWithinDueDate', 'payable_amount', 'payableAmount', 'PayableAmount', 'total_amount', 'totalAmount', 'TotalAmount', 'net_amount', 'netAmount', 'NetAmount', 'gross_amount', 'grossAmount', 'GrossAmount', 'amount_with_due_date', 'amountWithDueDate', 'AmountWithDueDate', 'amount_with_due', 'amountWithDue', 'AmountWithDue'],
            'amount_after_due' => ['amount_after_due', 'amountAfterDue', 'AmountAfterDue', 'amount_After_Due', 'Amount_After_Due', 'amount_after_due_date', 'amountAfterDueDate', 'AmountAfterDueDate', 'amount_After_Due_Date', 'Amount_After_Due_Date', 'amount_after_duedate', 'amountAfterDuedate', 'AmountAfterDuedate', 'amount_payable_after_due_date', 'amountPayableAfterDueDate', 'AmountPayableAfterDueDate'],
            'billing_month' => ['billing_month', 'billingMonth', 'BillingMonth', 'billing_Month', 'Billing_Month'],
            'date_paid' => ['date_paid', 'datePaid', 'DatePaid', 'date_Paid', 'Date_Paid'],
            'amount_paid' => ['amount_paid', 'amountPaid', 'AmountPaid', 'amount_Paid', 'Amount_Paid'],
            'tran_auth_id' => ['tran_auth_id', 'tranAuthId', 'TranAuthId', 'tran_Auth_Id', 'Tran_Auth_Id'],
            'contact_number' => ['contact_number', 'contactNumber', 'ContactNumber', 'contact_Number', 'Contact_Number'],
            'institution_id' => ['institution_id', 'institutionId', 'InstitutionId', 'institution_Id', 'Institution_Id'],
            'institution_name' => ['institution_name', 'institutionName', 'InstitutionName', 'institution_Name', 'Institution_Name'],
        ];

        foreach ($keysToMap as $baseKey => $variants) {
            $val = null;
            $found = false;
            foreach ($variants as $variant) {
                if (array_key_exists($variant, $data)) {
                    $val = $data[$variant];
                    $found = true;
                    break;
                }
            }
            if ($found) {
                foreach ($variants as $variant) {
                    $data[$variant] = $val;
                }
            }
        }

        return response()->json($data, $status, $headers);
    }
}
