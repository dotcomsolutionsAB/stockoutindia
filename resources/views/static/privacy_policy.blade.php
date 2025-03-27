@extends('layouts.app') {{-- Use your main layout --}}

@section('title', 'Privacy Policy')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">Privacy Policy</h1>
    <p><strong>Effective Date:</strong> 01-06-2024</p>

    <p>Stockout is committed to protecting your privacy. This Privacy Policy explains how we collect, use, store, and protect your information when you use our platform.</p>

    <h4 class="mt-4">1. Information We Collect</h4>
    <p>We collect the following information when users sign up for Stockout:</p>
    <ul>
        <li>Name</li>
        <li>Email</li>
        <li>Phone Number</li>
        <li>Address</li>
        <li>Business Details</li>
        <li>Payment Details</li>
    </ul>
    <p>We also use third-party analytics and tracking tools to enhance user experience and improve our services.</p>

    <h4 class="mt-4">2. How We Use Your Information</h4>
    <p>We use your information for the following purposes:</p>
    <ul>
        <li>Facilitating buyer and seller interactions</li>
        <li>Processing payments</li>
        <li>Marketing and advertising</li>
        <li>Improving user experience through analytics</li>
    </ul>

    <h4 class="mt-4">3. Data Storage & Security</h4>
    <ul>
        <li>User data is stored for 1 year.</li>
        <li>We implement security measures to protect user data.</li>
        <li>Users can request data deletion at any time by contacting our support team.</li>
        <li>We do not share user data with third parties.</li>
    </ul>
</div>
@endsection
