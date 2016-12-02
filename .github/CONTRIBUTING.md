# Contributing to the WHMCS eNom price sync module

:+1::tada: First off, thanks for taking the time to contribute! :tada::+1:

The following is a set of guidelines for contributing to this module. These are just guidelines, not rules. Please feel free to deviate from these guidelines if you want.

#### Table Of Contents

[What should I know before I get started?](#what-should-i-know-before-i-get-started)
  * [Code of Conduct](#code-of-conduct)

[How Can I Contribute?](#how-can-i-contribute)
  * [Reporting Bugs](#reporting-bugs)
  * [Suggesting Enhancements](#suggesting-enhancements)
  * [Your First Code Contribution](#your-first-code-contribution)
  * [Pull Requests](#pull-requests)

[Styleguides](#styleguides)
  * [Git Commit Messages](#git-commit-messages)
  * [PHP Styleguide](#code-styleguide)
  * [Documentation Styleguide](#documentation-styleguide)

## What should I know before I get started?

### Code of Conduct

This project adheres to the Contributor Covenant [code of conduct](CODE_OF_CONDUCT.md).
By participating, you are expected to uphold this code.
If you see unacceptable behavior, please comment on it, and make sure to page @ducohosting/php.

## How Can I Contribute?

### Reporting Bugs

This section guides you through submitting a bug report for the module. Following these guidelines helps maintainers and the community understand your report :pencil:, reproduce the behavior :computer: :computer:, and find related reports :mag_right:.

Before creating bug reports, please check [this list](#before-submitting-a-bug-report) as you might find out that you don't need to create one. When you are creating a bug report, please [include as many details as possible](#how-do-i-submit-a-good-bug-report). If you'd like, you can use [this template](#template-for-submitting-bug-reports) to structure the information.

#### Before Submitting A Bug Report

* **Check the [debugging guide](https://github.com/ducohosting/whmcs-enom-updater/wiki/Debugging).** You might be able to find the cause of the problem and fix things yourself. Most importantly, check if you can reproduce the problem [in the latest version of the module](https://github.com/ducohosting/whmcs-enom-updater/wiki/Checking-for-updates).
* **Perform a [cursory search](https://github.com/ducohosting/whmcs-enom-updater/issues)** to see if the problem has already been reported. If it has, add a comment to the existing issue instead of opening a new one.

#### How Do I Submit A (Good) Bug Report?

Bugs are tracked as [GitHub issues](https://guides.github.com/features/issues/).

Explain the problem and include additional details to help maintainers reproduce the problem:

* **Use a clear and descriptive title** for the issue to identify the problem.
* **Describe the exact steps which reproduce the problem** in as many details as possible. For example, start by describing your hosting environment. Make sure to be very detailed when explaining the steps you performed. Instead of saying you ended up on some page, tell us *exactly* how you got there (which buttons did you press?).
* **Provide specific examples to demonstrate the steps**. Does this problem only occur for a specific TLD? Maybe it only happens when your eNom balance is below a certain level. Give us as much detail as you think is required.
* **Describe the behavior you observed after following the steps** and point out what exactly is the problem with that behavior.
* **Explain which behavior you expected to see instead and why.**
* **Include screenshots and animated GIFs** which show you following the described steps and clearly demonstrate the problem. If you use the keyboard while following the steps, you can use [this tool](http://www.cockos.com/licecap/) to record GIFs on macOS and Windows, and [this tool](https://github.com/colinkeenan/silentcast) or [this tool](https://github.com/GNOME/byzanz) on Linux.
* **If you're reporting a Fatal Error**, include the PHP error log and any errors you saw on your screen. Include the error log in the issue in a [code block](https://help.github.com/articles/markdown-basics/#multiple-lines), a [file attachment](https://help.github.com/articles/file-attachments-on-issues-and-pull-requests/), or put it in a [gist](https://gist.github.com/) and provide link to that gist.
* **If the problem is related to performance**, include as many details about your environment as possible. Think of the CPU you use, what operating system/version, which WHMCS version, your distance to the server and anything else that might help us.
* **If the problem wasn't triggered by a specific action**, describe what you were doing before the problem happened and share more information using the guidelines below.

Provide more context by answering these questions:

* **Did the problem start happening recently** (e.g. after updating to a new version of the module) or was this always a problem?
* If the problem started happening recently, **can you reproduce the problem in an older version of the module?** What's the most recent version in which the problem doesn't happen? You can download older versions of the module from [the releases page](https://github.com/ducohosting/whmcs-enom-updater/releases).
* **Can you reliably reproduce the issue?** If not, provide details about how often the problem happens and under which conditions it normally happens.
* If the problem is related to the eNom API? (e.g. getting the eNom prices), **does the problem happen for all TLDs or only some?** Does this problem only happen with a certain type of TLDs (like country TLDs, or those requiring extra registrant information)?

Include details about your configuration and environment:

* **Which version of the module are you using?** You can get the exact version by visiting the WHMCS addon modules page
* **What's the name and version of the OS you're running WHMCS on**?

#### Template For Submitting Bug Reports

    [Short description of problem here]

    **Reproduction Steps:**

    1. [First Step]
    2. [Second Step]
    3. [Other Steps...]

    **Expected behavior:**

    [Describe expected behavior here]

    **Observed behavior:**

    [Describe observed behavior here]

    **Screenshots and GIFs**

    ![Screenshots and GIFs which follow reproduction steps to demonstrate the problem](url)

    **Module version:** [Enter Module version here]  
    **OS and version:** [Enter OS name and version here]  
    **WHMCS version:** [Enter WHMCS version here]  

    **Installed modules:**

    [List of installed modules here]

    **Additional information:**

    * Problem started happening recently, didn't happen in an older version of the module: [Yes/No]
    * Problem can be reliably reproduced, doesn't happen randomly: [Yes/No]
    * Problem happens with all TLDs, not only some TLDs: [Yes/No]

### Suggesting Enhancements

This section guides you through submitting an enhancement suggestion for the module, including completely new features and minor improvements to existing functionality. Following these guidelines helps maintainers and the community understand your suggestion :pencil: and find related suggestions :mag_right:.

Before creating enhancement suggestions, please check [this list](#before-submitting-an-enhancement-suggestion) as you might find out that you don't need to create one. When you are creating an enhancement suggestion, please [include as many details as possible](#how-do-i-submit-a-good-enhancement-suggestion). If you'd like, you can use [this template](#template-for-submitting-enhancement-suggestions) to structure the information.

#### Before Submitting An Enhancement Suggestion

* **Check the [debugging guide](https://github.com/ducohosting/whmcs-enom-updater/wiki/Debugging)** for tips — you might discover that the enhancement is already available. Most importantly, check if you're using [the latest version of the module](https://github.com/ducohosting/whmcs-enom-updater/wiki/Checking-for-updates).
* **Perform a [cursory search](https://github.com/ducohosting/whmcs-enom-updater/issues)** to see if the enhancement has already been suggested. If it has, add a comment to the existing issue instead of opening a new one.

#### How Do I Submit A (Good) Enhancement Suggestion?

Enhancement suggestions are tracked as [GitHub issues](https://guides.github.com/features/issues/). Create an issue on that repository and provide the following information:

* **Use a clear and descriptive title** for the issue to identify the suggestion.
* **Provide a step-by-step description of the suggested enhancement** in as many details as possible.
* **Describe the current behavior** and **explain which behavior you expected to see instead** and why.
* **Include screenshots and animated GIFs** which help you demonstrate the steps or point out the part of the module which the suggestion is related to. You can use [this tool](http://www.cockos.com/licecap/) to record GIFs on macOS and Windows, and [this tool](https://github.com/colinkeenan/silentcast) or [this tool](https://github.com/GNOME/byzanz) on Linux.
* **List some other modules or tools where this enhancement exists.**
* **Specify which version of the module you're using.** You can get the exact version by visiting the addon modules configuration page in WHMCS
* **Specify the name and version of the OS you're using.**

#### Template For Submitting Enhancement Suggestions

    [Short description of suggestion]

    **Steps which explain the enhancement**

    1. [First Step]
    2. [Second Step]
    3. [Other Steps...]

    **Current and suggested behavior**

    [Describe current and suggested behavior here]

    **Why would the enhancement be useful to most users**

    [Explain why the enhancement would be useful to most users]

    [List some other modules or tools where this enhancement exists]

    **Screenshots and GIFs**

    ![Screenshots and GIFs which demonstrate the steps or part of the module which the enhancement suggestion is related to](url)

    **Module Version:** [Enter module version here]
    **OS and Version:** [Enter OS name and version here]
    **WHMCS Version:** [Enter WHMCS version here]

### Your First Code Contribution

Unsure where to begin contributing to the module? You can start by looking through these `beginner` and `help-wanted` issues:

* [Beginner issues][beginner] - issues which should only require a few lines of code, and a test or two.
* [Help wanted issues][help-wanted] - issues which should be a bit more involved than `beginner` issues.

Both issue lists are sorted by total number of comments. While not perfect, number of comments is a reasonable proxy for impact a given change will have.

### Pull Requests

* Include screenshots and animated GIFs in your pull request whenever possible.
* Follow the [styleguide](#code-styleguide)
* Document new code based on the
  [Documentation Styleguide](#documentation-styleguide)
* End files with a newline.
* Using a plain `return` when returning explicitly at the end of a function.
    * Not `return null`, `return undefined`, `null`, or `undefined`

## Styleguides

### Git Commit Messages

* Use the present tense ("Add feature" not "Added feature")
* Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
* Limit the first line to 72 characters or less
* Reference issues and pull requests liberally
* Consider starting the commit message with an applicable emoji:
    * :art: `:art:` when improving the format/structure of the code
    * :racehorse: `:racehorse:` when improving performance
    * :non-potable_water: `:non-potable_water:` when plugging memory leaks
    * :memo: `:memo:` when writing docs
    * :bug: `:bug:` when fixing a bug
    * :fire: `:fire:` when removing code or files
    * :white_check_mark: `:white_check_mark:` when adding tests
    * :lock: `:lock:` when dealing with security
    * :arrow_up: `:arrow_up:` when upgrading dependencies
    * :arrow_down: `:arrow_down:` when downgrading dependencies

### Code Styleguide

* All functions must begin with `enomPricingUpdater_` to ensure there are no conflicts
* All functions must have a PHPDoc comment describing their functionality

### Documentation Styleguide

* Use [PHPDoc](https://www.phpdoc.org/).
* Use [Markdown](https://daringfireball.net/projects/markdown).

[beginner]:https://github.com/ducohosting/whmcs-enom-updater/labels/beginner
[help-wanted]:https://github.com/ducohosting/whmcs-enom-updater/labels/help%20wanted
