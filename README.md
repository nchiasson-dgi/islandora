# Islandora

## Introduction

Islandora Fedora Repository Module

For installation and customization instructions please see the
[wiki](https://wiki.duraspace.org/display/ISLANDORA/Islandora).

## Requirements

This module requires the following modules/libraries:

* [Tuque](https://github.com/islandora/tuque)

Tuque is expected to be in one of two paths:

* sites/all/libraries/tuque (libraries directory may need to be created)
* islandora_folder/libraries/tuque

More detailed requirements are outlined in the
[wiki](https://wiki.duraspace.org/display/ISLANDORA/milestone+5+-++Installing+the+Islandora+Essential+Modules).

## Installation

Before installing Islandora the XACML policies located
[here](https://github.com/Islandora/islandora-xacml-policies) should be copied
into the Fedora global XACML policies folder. This will allow "authenticated
users" in Drupal to access Fedora API-M functions. It is to be noted that the
`permit-upload-to-anonymous-user.xml` and `permit-apim-to-anonymous-user.xml`
files do not need to be present unless requirements for anonymous ingesting
are present.

You will also have to remove some default policies if you want full
functionality as well.

Remove `deny-purge-datastream-if-active-or-inactive.xml` to allow for purging
of datastream versions.

More detailed information can be found in the 'Set XACML Policies' in the
[wiki](https://wiki.duraspace.org/display/ISLANDORA/milestone+1+-+Installing+Fedora).

## Configuration

The `islandora_drupal_filter` passes the username of 'anonymous' through to
Fedora for unauthenticated Drupal Users. A user with the name of 'anonymous'
may have XACML policies applied to them that are meant to be applied to Drupal
users that are not logged in or vice-versa. This is a potential security issue
that can be plugged by creating a user named 'anonymous' and restricting access
to the account.

Drupal's cron can be run to remove expired authentication tokens.

## Troubleshooting/Issues

Having problems or solved one? Create an issue, check out the Islandora Google
groups.

* [Users](https://groups.google.com/forum/?hl=en&fromgroups#!forum/islandora)
* [Devs](https://groups.google.com/forum/?hl=en&fromgroups#!forum/islandora-dev)

or contact [discoverygarden](http://support.discoverygarden.ca).

## Maintainers/Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

## Development

If you would like to contribute to this module, please check out the helpful
[Documentation](https://github.com/Islandora/islandora/wiki#wiki-documentation-for-developers),
[Developers](http://islandora.ca/developers) section on Islandora.ca and create
an issue, pull request and or contact
[discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
