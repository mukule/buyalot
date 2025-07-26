@component('mail::message')
# Hello {{ $app->first_name }},

@isset($app->verified)
@if($app->verified)
**Congratulations!**  
Your seller application has been **verified**. Your products will now be available to customers on our platform.

You may now proceed to set up your store and manage your listings.
@else
 **Update:**  
Your seller account is no longer verified. As a result, your products are currently not available to buyers.

This change may have been made due to activity that appears to violate our seller terms of service or platform guidelines.

If you believe this was done in error or you would like further clarification, please contact our support team. We are here to assist you.
@endif

@endisset

Thanks,  
{{ config('app.name') }} Team
@endcomponent
