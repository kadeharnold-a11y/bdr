/** Map backend OTP/auth error codes to user-facing messages. */
export function otpErrorMessage(err, fallback = 'Something went wrong. Please try again.') {
  const code = err?.response?.data?.error?.code
  const message = err?.response?.data?.error?.message

  if (message) return message

  return (
    {
      INVALID_PHONE: 'Enter a valid 9-digit Ghana mobile number.',
      INVALID_EMAIL: 'Enter a valid email address.',
      INVALID_CHANNEL: 'Choose text message or email for your verification code.',
      INVALID_CREDENTIALS: 'Phone number or PIN is incorrect.',
      NO_EMAIL_ON_FILE: 'No email is on file for this account. Use text message instead.',
      OTP_DELIVERY_FAILED: 'Could not send your verification code. Try again shortly.',
      SMS_NOT_CONFIGURED: 'SMS is not configured on the server. Choose email instead, or contact support.',
      SMS_DELIVERY_FAILED: 'Could not send the text message. Try email instead or try again later.',
      EMAIL_NOT_CONFIGURED: 'Email delivery is not configured on the server. Choose text message instead, or contact support.',
      EMAIL_DELIVERY_FAILED: 'Could not send the verification email. Check the address and try again.',
      OTP_EXPIRED: 'That code has expired. Request a new one.',
      OTP_INCORRECT: 'Incorrect code. Please try again.',
      OTP_ALREADY_USED: 'This code has already been used. Request a new one.',
      SESSION_NOT_FOUND: 'Your verification session expired. Request a new code.',
      INVALID_OTP_FORMAT: 'Enter the 6-digit verification code.',
      TOO_MANY_REQUESTS: 'Too many attempts. Please wait a few minutes and try again.',
      PHONE_ALREADY_REGISTERED: 'This phone number already has an account. Please log in instead.',
    }[code] ?? fallback
  )
}

export function otpDestinationLabel(channel, sentTo, maskedPhone) {
  if (channel === 'email') return sentTo
  return maskedPhone
}
