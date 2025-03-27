@extends('layouts.app') {{-- Replace with your main layout --}}

@section('title', 'Terms & Conditions')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">Terms & Conditions</h1>
    <p><strong>Effective Date:</strong> 01-06-2024</p>

    <p>By using Stockout, you agree to comply with the following terms and conditions.</p>

    <h4 class="mt-4">1. User Responsibilities</h4>
    <ul>
        <li>Users must provide accurate product listings.</li>
        <li>Buyers and sellers must comply with all applicable laws and regulations.</li>
        <li>Stockout is not responsible for legal compliance of users.</li>
    </ul>

    <h4 class="mt-4">2. Platform Limitations</h4>
    <ul>
        <li>Stockout is not liable for any losses, damages, or disputes arising between buyers and sellers.</li>
        <li>We do not guarantee the sale of any product listed on our platform.</li>
    </ul>

    <h4 class="mt-4">3. Listing Fees & Payments</h4>
    <ul>
        <li>Sellers must pay listing fees at the time of listing each product.</li>
        <li>This feature may change in the future at Stockout’s discretion.</li>
    </ul>

    <h4 class="mt-4">4. Account & Listing Management</h4>
    <ul>
        <li>Stockout reserves the right to remove listings or ban users for violations of our policies or misuse of the platform.</li>
        <li>There are no penalties for non-compliance, but Stockout may take necessary actions to maintain platform integrity.</li>
    </ul>

    <p class="mt-4">By using Stockout, you acknowledge and agree to these terms. If you have any questions, please contact our support team.</p>
</div>
@endsection
