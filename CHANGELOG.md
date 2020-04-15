# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]


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
