# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## [0.5.0] - 2018-05-03

### Added

 - Allow services referenced in config to be prefixed with @ (#41)
 - Add FQCN alias for `Prooph\Common\messaging\MessageFactory` (#45, thanks to @gquemener)

### Deprecated

 - Deprecate using a FQCN instead of a service id for projections.
   Support for this kind of configuration will be removed in v1.0. (#43) 

### Changed

 - Enhance validation of repository configuration (#44, thanks to @gquemener) 

### Removed

 - **[BC-BREAK]** Remove automatically created aliases for projections and projection-managers (#43)

### Fixed

 - Fix projection configuration via tags (#42, #43)


## [0.4.0] - 2018-02-21

### Changed

 - Support Symfony 4, drop Symfony <= 3.3 (#33, #38, thanks to @kejwmen, @mkurzeja) 
