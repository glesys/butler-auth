# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]


## [7.1.1] - 2025-12-19

### Changed
- Add explicit table name to `AccessToken` to prevent implicit lookup through pluralization.


## [7.1.0] - 2025-05-13

### Changed
- Support Laravel 12.


## [7.0.0] - 2024-05-28

### Changed
- **BREAKING:** Require Laravel 11.

## [6.0.0] - 2023-03-01

### Changed
- **BREAKING:** Require Laravel 10.

### Added
- Laravel pint.

## [5.0.0] - 2022-02-14

### Changed
- **BREAKING:** Require Laravel 9.

## [4.1.1] - 2021-10-05

### Fixed
- Catch cache exceptions so authentication can proceed when i.e. redis is unreachable.

## [4.0.0] - 2021-06-09

### Changed
- **BREAKING:** Require PHP 8 and Laravel 8.
- **BREAKING:** Store credentials in database instead of using JWT tokens.


## [3.0.0] - 2020-12-09

### Added
- Support PHP 8.

### Changed
- **BREAKING:** Requires PHP 7.4 and Laravel 7.


## [2.2.0] - 2020-10-06

### Changed
- Support Laravel 8.


## [2.1.0] - 2020-08-03

### Added
- Return a `JwtUser` instance from `JwtGuard` instead of `GenericUser` (`JwtUser` is a subclass of `GenericUser` to prevent a breaking change) with support for returning the value of `sub` from `getAuthIdentifier()`.


## [2.0.0] - 2020-04-15

### Added
- Add command for generating secret key

### Changed
- *BREAKING*: Changed signature for GenerateToken command.


## [1.4.0] - 2020-03-13

### Changed
- Require PHP 7.2.5
- Support Laravel 7.

### Added
- Add package discovery to composer.json


## [1.3.0] - 2019-09-18

### Changed
- Support multiple values for `aud` and `iss` in the `required_claims` configuration.

## [1.2.0] - 2019-09-09

### Changed
- Support Laravel 6 🎉.


## [1.1.0] - 2018-04-10

### Changed
- Make the configuration compatible with Laravel.


## [1.0.0] - 2018-02-24

### Added
- Add JwtGuard, ServiceProvider and GenerateToken command.
