@component('mail::message')

{{-- Logo --}}
<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $logoUrl }}" alt="{{ $appName }} Logo" style="width: 120px; height: auto; max-width: 100%;" />
</div>

# New Seller Application Received

A new seller application has been submitted to **{{ $appName }}**.

### 🧾 Seller Info:
- **Name:** {{ $application->first_name }} {{ $application->last_name }}
- **Email:** {{ $application->contact_email }}
- **Phone:** {{ $application->contact_phone }}
@if (!empty($application->company_legal_name))
- **Business Name:** {{ $application->company_legal_name }}
@endif
- **Business Type:** {{ ucfirst($application->business_type) }}
- **Primary Product Category:** {{ $application->primary_product_category ?? 'N/A' }}

---

@if(isset($adminUser))
### 📋 Handled by:
- **Admin Name:** {{ $adminUser->name }}
- **Email:** {{ $adminUser->email }}
@endif

You can log in to the admin panel to review or take action on this application.

Thanks,<br>
{{ $appName }} System

@endcomponent
