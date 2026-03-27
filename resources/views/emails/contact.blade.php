{{-- resources/views/emails/contact.blade.php --}}
<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; background: #f4f4f4; padding: 30px;">
    <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; padding: 30px;">
        <h2 style="color: #1d4ed8;">New Contact Form Submission</h2>
        <hr>
        <p><strong>Name:</strong> {{ $formData['name'] }}</p>
        <p><strong>Email:</strong> {{ $formData['email'] }}</p>
        <p><strong>Message:</strong></p>
        <p style="background: #f9f9f9; padding: 15px; border-radius: 6px;">
            {{ $formData['message'] }}
        </p>
        <hr>
        <small style="color: #888;">Sent from zihubridge.com contact form</small>
    </div>
</body>
</html>