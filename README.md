# Codeception TestRail Integration Module

This [Codeception](https://codeception.com) extension provides functionality for tests to report results to
[TestRail](https://testrail.com) using the [TestRail API v2](http://docs.gurock.com/testrail-api2/start).

**Note:** The extension currently only supports the `Cest` Codeception test format.  It cannot report PHPUnit or `Cept`
tests.

## Installation

The easiest way to install this plugin is using [Composer](https://getcomposer.org/).  You can install module
by running:

```
composer require --dev bookit/codeception-testrail
```

## Theory of Operation
The extension hooks the following Codeception events: `suite.after`, `test.success`, `test.skipped`, `test.incomplete`,
`test.error`, and `test.fail`.  The extension also provides an `_initialize()` method and some other helper methods.

During the `_initialize()` step, the module will fetch the project details and create a new plan for the run.  The plan
name can be procedurally generated but by default it will be the date and time formatted as *Y-m-d H:i;s*.  Also during
this step the default status list is overriden with any from the config.

During the `test.success`, `test.skipped`, `test.incomplete`, `test.error`, and `test.fail` events, a result is recorded
for that event.  TestRail Suites and TestRail Test Cases are set for the test using annotations (`@tr-suite` and
`@tr-case`).  The TestRail Suite can either be set at the class level or the method level; precedent is give to the
method level annotation.  The TestRail Test Case can only be set at the method level.  Additionally, the elapsed time
of the test will be formatted and recorded.

During the `after.suite` event, the collected results will be transmitted to TestRail.  This is accomplished by
performing two actions.  The first action is to create a TestRail Test Plan Entry for each of the TestRail suites in the
result set.  The Test Plan Entry will only contain Test Cases which were also registered.  The Test Plan Entry will
be named after the Codeception suite and the TestRail suite, separated by a colon.

After the Test Plan Entry is created, the Test Run ID is captured from the response and the test results are transmitted
using the bulk test result action.  Before the results are passed to TestRail, they're filtered to remove any results
which set the TestRail System Status of *Untested*.  The TestRail API issues an error when a result attempts to set this
status.

The default status map is:

| Codeception Status | TestRail Status | TestRail Status ID |
|:------------------ |:---------------:|:------------------:|
| success            | Success         | 1                  |
| failure            | Failed          | 5                  |
| error              | Failed          | 5                  |
| incomplete         | Success         | 1                  |
| skipped            | Untested        | 3                  |

## Configuration

The extension requires four configuration parameters to be set (`user`, `apikey`, `project`).  There are additional
configuration options for overriding statuses and disabling the connection to TestRail.

To enable the extension the following can be added to your `codeception.yml` config file:

```yaml
extensions:
    enabled:
        - BookIt\Codeception\TestRail\Extension
```

Global configuration options (like the `user` and `apikey`) should also be set in the `codeception.yml` config:

```yaml
extensions:
    config:
        BookIt\Codeception\TestRail\Extension:
            enabled: false                    # When false, don't communicate with TestRail (optional; default: true)
            user: 'mark.randles@bookit.com'   # A TestRail user (required)
            apikey: 'REDACTED'                # A TestRail API Key (required)
	  		url: 'https://myurl.testrail.com' # The base URL for you TestRail Instance
            project: 9                        # TestRail Project ID (required)
            status:
                success: 1                    # Override the default success status (optional)
                skipped: 11                   # Override the default skipped status (optional)
                incomplete: 12                # Override the default incomplete status (optional)
                failed: 5                     # Override the default failed status (optional)
                error: 5                      # Override the default error status (optional)
```

## More Information

* [Codeception](https://codeception.com)
* [TestRail](https://testrail.com)
* [TestRail API](http://docs.gurock.com/testrail-api2/start)
* [TestRail API Keys & Authentication](http://docs.gurock.com/testrail-api2/accessing#username_and_api_key)

## License

MIT

(c) BookIt.com 2016

