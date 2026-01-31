# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
