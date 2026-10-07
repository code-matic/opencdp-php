# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-10-07

### Changed

- Requires the `curl` PHP extension (`ext-curl`). Send failover relies on cURL error codes to prove a host was never reached; without them a failed send is not retried.
- Transport failures with no HTTP response (for example a timeout) are reported with status `0` for email, push and SMS, matching WhatsApp. They were previously reported as `400`.
- `SendEmailRequest::withAttachment()` keeps numeric filenames such as `"123"`, and attachments are always sent as a JSON object.
- `SendEmailRequest::withAttachmentFile()` rejects files over 2 MB without reading them into memory.
- `attachments` is now supported by the backend and no longer logs a "not yet supported" warning
- Message sends (`/v1/send/*`) now fail over to a fallback gateway host only when the primary provably did not process the request (connection refused, DNS failure, or Cloudflare 521/523/525/526). Timeouts, 4xx and other 5xx, including 502, 503, 504 and 522, are returned without retrying, to avoid delivering the same message twice. Identify, track and device registration are unchanged.
- Message sends no longer follow HTTP redirects. A 3xx is returned as an error and is not retried on a fallback host, because the original host may already have accepted the message.
- Attachment content must be valid base64 (standard or url-safe alphabet but not both, `=` padding only at the end). Malformed content that a lenient decoder would partly accept is now rejected before sending.

### Added

- `sendWhatsApp()` - Send WhatsApp messages using a saved WhatsApp transactional
- Email attachments: `SendEmailRequest::withAttachment()` and `SendEmailRequest::withAttachmentFile()` return a copy with the file base64-encoded, and `sendEmail()` validates attachments against the gateway's limits (at most 5 files, 2 MB decoded in total) before sending

## [1.0.2] - 2026-09-29

### Changed

- Default gateway fallback hosts updated from `api.opencdp.com` / `api.opencdp.xyz` to `api.open-cdp.com` / `api.open-cdp.xyz` (primary remains `api.opencdp.io`)

## [1.0.0] - 2026-01-29

### Added

- Initial release of OpenCDP PHP SDK
- Core functionality:
  - `identify()` - Identify persons in CDP
  - `track()` - Track events
  - `registerDevice()` - Register devices for push notifications
  - `sendEmail()` - Send transactional emails
  - `sendPush()` - Send push notifications
  - `sendSms()` - Send SMS messages
- Comprehensive validation for all request types
- Optional Customer.io dual-write integration
- Debug logging support
- Custom logger interface
- Full type safety with PHP 8.0+ typed properties
- Exception handling with specialized exception types (`$errorCode` property; `$code` not used to avoid conflict with PHP’s `\Exception::$code`)
- Connection testing with `ping()` method
- Support for transactional templates and raw messages
- Comprehensive documentation and examples
- Test suite covering validators, exceptions, configuration, types, and client methods
- Input length validation:
  - Identifiers: Maximum 255 characters for string identifiers
  - Event names: Maximum 255 characters
  - Email addresses: Maximum 254 characters (per RFC 5321)
- Enhanced validation for email array fields (`bcc`, `cc`): type checking and clear error messages
- Safe response body extraction for error handling (seekable and non-seekable streams)
- PHPDoc and docs for return types and validation rules

### Requirements

- PHP 8.0 or higher
- Guzzle HTTP client

### Optional

- Customer.io PHP SDK for dual-write functionality
