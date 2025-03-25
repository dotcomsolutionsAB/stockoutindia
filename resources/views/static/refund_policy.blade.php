@extends('layouts.app') {{-- Replace with your main layout if different --}}

@section('title', 'Refund Policy')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">Refund Policy</h1>
    <p><strong>Effective Date:</strong> 01-06-2024</p>

    <p>At Stockout, we strive to provide a seamless experience for sellers and buyers. Please read our refund policy carefully before listing a product.</p>

    <h4 class="mt-4">1. Listing Fee Policy</h4>
    <ul>
        <li>The listing fee is non-refundable once the product is listed.</li>
        <li>No partial refunds are offered since our resources are put to work immediately upon listing.</li>
    </ul>

    <h4 class="mt-4">2. Premium Marketing Services</h4>
    <ul>
        <li>Any paid advertising services (e.g., Instagram/Facebook Ads) are non-refundable.</li>
    </ul>

    <h4 class="mt-4">3. Buyer Protection & Disputes</h4>
    <ul>
        <li>Stockout does not take responsibility for disputes between buyers and sellers.</li>
        <li>Buyers are responsible for verifying product details before purchasing.</li>
        <li>Stockout does not validate the product condition, and no refunds will be issued if a product is misrepresented.</li>
    </ul>
</div>
@endsection
