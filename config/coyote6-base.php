<?php

return [


    //
    //--------------------------------------------------------------------------
    // Machine Name
    //--------------------------------------------------------------------------
    //
    // Sets the method for how machine names are built, and any parameters
    // that method accepts.
    //
    // Every method is run against Str::ascii($source) first, regardless of
    // which one is selected below -- this is why "ascii" itself is not a
    // valid option. Only Str::slug() transliterates non-ASCII characters on
    // its own; every other method here either passes them through untouched
    // or (this package's own pureKebab/pureSnake/strictKebab/strictSnake/dot
    // macros) mangles them, so the conversion always happens up front instead
    // of relying on whichever method is chosen to handle it.
    //

    'machine_name' => [

        // Field
        //
        // The attribute the generated machine name is written to. Only used
        // by the MachineName trait -- MachineNameAsId always writes to the
        // model's primary key instead.
        //
        'field' => 'machine_name',
        

        // Reference
        //
        // The attribute the raw machine name text is read from.
        //
        'reference' => 'name',


        // Method
        //
        // Option            | Example (Test - My Machine Name)  | Default | coyote6/laravel-str
        //----------------------------------------------------------------------
        // "strictKebab"     | test-my-machine-name              | Y       | Y
        // "strictSnake"     | test_my_machine_name              |         | Y
        // "pureKebab"       | test---my-machine-name            |         | Y
        // "pureSnake"       | test___my_machine_name            |         | Y
        // "kebab"           | test--my-machine-name             |         |
        // "snake"           | test-_my_machine_name             |         |
        // "dot"             | test.my.machine.name              |         | Y
        // "slug"            | test-my-machine-name              |         |
        // "studly"          | TestMyMachineName                 |         |
        // "pascal"          | TestMyMachineName                 |         |
        // "camel"           | testMyMachineName                 |         |
        // "lower"           | test - my machine name            |         |
        // "upper"           | TEST - MY MACHINE NAME            |         |
        // "deduplicate"     | Test - My Machine Name            |         |
        // "transliterate"   | Test - My Machine Name            |         |
        //
        // @see https://packagist.org/packages/coyote6/laravel-str
        //

        'method' => 'strictKebab',

    
        // Method Parameters
        //
        // Method            | Params                                  | Default Values
        //----------------------------------------------------------------------
        // "strictKebab"     |                                         |
        // "strictSnake"     |                                         |
        // "pureKebab"       | $consecutiveDashes                      | 0 - unlimited
        // "pureSnake"       | $consecutiveUnderscores                 | 0 - unlimited
        // "kebab"           |                                         |
        // "snake"           | $delimiter                              | '_'
        // "dot"             |                                         |
        // "slug"            | $separator, $language, $dictionary      | ['-','en', ['@'=>'at']]
        // "studly"          | $normalize                              | false
        // "pascal"          | $normalize                              | false
        // "camel"           |                                         |
        // "lower"           |                                         |
        // "upper"           |                                         |
        // "deduplicate"     | $characters                             | ' ' (single space)
        // "transliterate"   | $unknown, $strict                       | '?', false
        //

        'method_parameters' => null,


    ],


    //
    //--------------------------------------------------------------------------
    // Author
    //--------------------------------------------------------------------------
    //
    // Configures the Author trait, which stamps the current authenticated
    // user's id onto new records.
    //

    'author' => [

        // Field
        //
        // The attribute the current user's id is written to.
        //
        'field' => 'author_id',

    ],


    //
    //--------------------------------------------------------------------------
    // Original Author
    //--------------------------------------------------------------------------
    //
    // Configures the OriginalAuthor trait, which stamps the current
    // authenticated user's id onto new records the same way Author does,
    // independently of it -- see OriginalAuthor's own @ai note for why it
    // doesn't derive its value from Author/author_id.
    //

    'original_author' => [

        // Field
        //
        // The attribute the current user's id is written to.
        //
        'field' => 'original_author_id',

    ],


    //
    //--------------------------------------------------------------------------
    // Client
    //--------------------------------------------------------------------------
    //
    // Configures the Client trait, which stamps the current authenticated
    // user's client id onto new records.
    //

    'client' => [

        // Field
        //
        // The attribute the client id is written to.
        //
        'field' => 'client_id',


        // Reference
        //
        // The current user's client id attribute.
        //
        'reference' => 'client_id',

    ],


    //
    //--------------------------------------------------------------------------
    // Slug
    //--------------------------------------------------------------------------
    //
    // Configures the Slug trait, which generates a slug via Str::slug().
    //

    'slug' => [

        // Field
        //
        // The attribute the generated slug is written to.
        //
        'field' => 'slug',


        // Reference
        //
        // The attribute the raw slug text is read from.
        //
        'reference' => 'name',


        // Separator
        //
        // Matches Str::slug()'s $separator parameter.
        //
        'separator' => '-',


        // Language
        //
        // Matches Str::slug()'s $language parameter.
        //
        'language' => 'en',


        // Dictionary
        //
        // Matches Str::slug()'s $dictionary parameter.
        //
        'dictionary' => ['@' => 'at'],

    ],


];
