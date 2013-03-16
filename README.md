# Mandrill support for Zend Framework (1.X) #

This code is based on Mandrill's original PHP API (version 1.0.17), located at: https://packagist.org/packages/mandrill/mandrill

## How to use ##

1. Copy files to your library directory

2. Initiate the code

    ```php
    // the following examples uses a pre-generated Mandrill template

    $mandrill = new Mandrill_Init(API_KEY);
    $template_content = array(
        array(
            'name'    => 'example_variable',
            'content' => 'example value for the variable'
        )
    );
    $message = array(
        'subject'    => 'Some nice subject',
        'from_email' => 'example@example.org',
        'from_name'  => 'Example'
        'to'         => array(
            'email' => $recipient_email,
            'name'  => $recipient_name
        ),
        'inline_css' => true
    );
    $mandrill->messages->sendTemplate('template_name', $template_content, $message);
    ```

3. Add your API key and modify the parameters to fit your needs.

4. Enjoy and don't spam! :)

## Changelog ##

### 0.1 (March 16th 2013) ###

* Initial release