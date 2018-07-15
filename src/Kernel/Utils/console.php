<?php

use Colors\Color;

function gotoAction($selectNum = null)
{
    $showIntro = function ($rows = null)
    {
        $tb = new Console_Table(CONSOLE_TABLE_ALIGN_LEFT, CONSOLE_TABLE_BORDER_ASCII);
        $tb->addRow([APP_NAME.' - '.APP_COPYRIGHT]);
        if (!is_null($rows)) {
            foreach ($rows as $i => $row) {
                $tb->addSeparator();
                $tb->addRow([$row]);
            }
        }
        print($tb->getTable().PHP_EOL);
    };
    
    if (!is_null($selectNum))
        goto newAction;
    
    $showIntro();
    foreach (APP_ACTION_MAP as $i => $action) {
        $n = $i+1;
        print("{$n} - {$action::$ACTION_NAME}   ");
        if ($n%4 === 0) print(PHP_EOL);
    }
    
    print(PHP_EOL.PHP_EOL);
    $options = [];
    foreach (APP_ACTION_MAP as $i => $action) {
        $options[$i+1] = $action::$ACTION_NAME . " ({$action})";
    }
    $selectNum = select('选择 Action: ', $options);
    print(PHP_EOL);
    clearScreen();
    
    newAction:
    $selectI = intval($selectNum)-1;
    $className = APP_ACTION_MAP[$selectI];
    $actionName = $className::$ACTION_NAME;
    $showIntro(["-> {$actionName}", "-> {$className}"]);
    new $className([]);
}

function _O($msg)
{
    output($msg);
}

function _I($msg)
{
    print((new Color('[消息]' . $msg))->light_blue . PHP_EOL);
}

function _S($msg)
{
    print((new Color('[成功]' . $msg))->light_green . PHP_EOL);
}

function _W($msg)
{
    print((new Color('[警告]' . $msg))->light_yellow . PHP_EOL);
}

function _E($msg)
{
    print((new Color('[错误]' . $msg))->light_red . PHP_EOL);
}

/**
 * Prints text to STDOUT appended with a carriage return (PHP_EOL).
 *
 * @param string $string the text to print
 */
function output($string = null)
{
    print($string . PHP_EOL);
}

/**
 * Asks the user for input. Ends when the user types a carriage return (PHP_EOL). Optionally, It also provides a
 * prompt.
 *
 * @param string $prompt the prompt to display before waiting for input (optional)
 * @return string the user's input
 */
function input($prompt = null)
{
    if (!is_null($prompt)) {
        fwrite(STDOUT, $prompt);
    }
    
    return trim(fgets(STDIN));
}

/**
 * Prompts the user for input and validates it.
 *
 * @param string $text prompt string
 * @param array $options the options to validate the input:
 *
 * - `required`: whether it is required or not
 * - `default`: default value if no input is inserted by the user
 * - `pattern`: regular expression pattern to validate user input
 * - `validator`: a callable function to validate input. The function must accept two parameters:
 * - `input`: the user input to validate
 * - `error`: the error value passed by reference if validation failed.
 *
 * @return string the user input
 */
function prompt($text, $options = [])
{
    $options = array_merge(
        [
            'required' => false,
            'default' => null,
            'pattern' => null,
            'validator' => null,
            'error' => 'Invalid input.',
        ],
        $options
    );
    $error = null;
    
    top:
    $input = $options['default']
        ? input("$text [" . $options['default'] . '] ')
        : input("$text ");
    
    if ($input === '') {
        if (isset($options['default'])) {
            $input = $options['default'];
        } elseif ($options['required']) {
            _E($options['error']);
            goto top;
        }
    } elseif ($options['pattern'] && !preg_match($options['pattern'], $input)) {
        _E($options['error']);
        goto top;
    } elseif ($options['validator'] &&
        !call_user_func_array($options['validator'], [$input, &$error])
    ) {
        _E(isset($error) ? $error : $options['error']);
        goto top;
    }
    
    return $input;
}

/**
 * Asks user to confirm by typing y or n.
 *
 * A typical usage looks like the following:
 *
 * ```php
 * if (Console::confirm("Are you sure?")) {
 *     echo "user typed yes\n";
 * } else {
 *     echo "user typed no\n";
 * }
 * ```
 *
 * @param string $message to print out before waiting for user input
 * @param bool $default this value is returned if no selection is made.
 * @return bool whether user confirmed
 */
function confirm($message, $default = false)
{
    while (true) {
        print($message . ' (yes|no) [' . ($default ? 'yes' : 'no') . ']:');
        $input = trim(input());
        
        if (empty($input)) {
            return $default;
        }
        
        if (!strcasecmp($input, 'y') || !strcasecmp($input, 'yes')) {
            return true;
        }
        
        if (!strcasecmp($input, 'n') || !strcasecmp($input, 'no')) {
            return false;
        }
    }
}

/**
 * Gives the user an option to choose from. Giving '?' as an input will show
 * a list of options to choose from and their explanations.
 *
 * @param string $prompt the prompt message
 * @param array $options Key-value array of options to choose from. Key is what is inputed and used, value is
 * what's displayed to end user by help command.
 *
 * @return string An option character the user chose
 */
function select($prompt, $options = [])
{
    top:
    print("$prompt [" . implode(',', array_keys($options)) . ',?]: ');
    $input = input();
    if ($input === '?') {
        foreach ($options as $key => $value) {
            output(" $key - $value");
        }
        output(' ? - Show help');
        goto top;
    } elseif (!array_key_exists($input, $options)) {
        goto top;
    }
    
    return $input;
}

/**
 * Clears entire screen content by sending ANSI control code ED with argument 2 to the terminal.
 * Cursor position will not be changed.
 * **Note:** ANSI.SYS implementation used in windows will reset cursor position to upper left corner of the screen.
 */
function clearScreen()
{
    echo "\033[2J";
}
