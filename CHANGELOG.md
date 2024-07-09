# WC Module Change Log

## [3.0.0] - 2023-06-28

### Added

Added Apple Pay intergration for direct Apple Pay payments from the checkout or cart page.
Added Gateway validation option for Apple Pay.

## [3.0.1] - 2023-08-17

### Added

Version file.
ChangeLog file.
Module version sent in transactions merchant data.

## [3.0.2] - 2023-09-22

### Added

Fields added to Apple Pay gateway validation request.

## [3.0.3] - 2023-10-10

### Fixed

Incorrect action when initial subscription payment amount is 0.
Missing customer country on hosted form.

## [3.0.4] - 2023-11-02

### Fixed

Incorrect handling of non 3DS or frictionless transactions 
using direct integration.

## [3.0.5] - 2023-11-20

### Fixed

Missing merchant details when refunding.

## [3.0.6] - 2024-04-24

### Fixed

Function called incorrectly error appearing in log.

## [3.1.0] - 2024-05-08

### Fixed

Incorrect Apple Pay validation URL being passed to Gateway.

Missing WorldWide shipping zones on Apple Pay shipping selection.

Shipping zones that require a coupon will now not be returned unless a valid
coupon is present.
