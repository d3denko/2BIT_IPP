<?php


/*
 * @AUTHOR  : Denis Horil
 * @LOGIN   : xhoril01
 * @EMAIL   : xhoril01@stud.fit.vutbr.cz
 * @PROJECT : VUT IPP Projekt 1 - Analyzator kodu v IPPcode22
 * 
 * 
 * Classes for main script parser.php
 * 
*/


ini_set('display_errors', 'stderr');

// Class with return codes for source files
final class RETURN_CODES
{
    public const
        GREAT_SUCCESS = 0,
        MISSING_PARAMETER = 10,
        OPENING_INPUT_ERROR = 11,
        OPENING_OUTPUT_ERROR = 12,
        MISSING_HEADER = 21,
        UNKNOWN_OPCODE = 22,
        PARSE_ERROR = 23,
        TEST_ERROR = 41,
        INTERNAL_ERROR = 99;
}


// Return staments depending on return codes and terminates program
final class EXITUS
{
    /*
     * @param int $return_code Number of exit code
     * @param ?int $lineNumber Line number (optional parameter)
     * @return void
    */
    public static function error_statement(int $return_code, int $line_num = 0, string $script_arg=null):void
    {
        switch($return_code)
        {
            case 0:
                exit($return_code);
                
            case 10:
                fprintf(STDERR, "ERROR: Missing script parameter or invalid combination of parameters\n");
                exit($return_code);
                
            case 11:
                fprintf(STDERR, "ERORR: An error occured while opening input files\n");
                exit($return_code);

            case 12:
                fprintf(STDERR, "ERROR: An error occured while opening output files\n");
                exit($return_code);

            case 21:
                fprintf(STDERR, "ERROR: Invalid or missing header '.IPPcode22' in source file\n");
                exit($return_code);

            case 22:
                fprintf(STDERR, "ERROR: Invalid or unknown opcode on line ".$line_num."\n");
                exit($return_code);

            case 23:
                fprintf(STDERR, "ERROR: Lexical or syntax error in source file on line ".$line_num."\n");
                exit($return_code);

            case 41:
                fprintf(STDERR, "ERROR: Cannot find file or file has restricted access in argument : ".$script_arg."\n");
                exit($return_code);
                
            case 99:
                fprintf(STDERR, "ERROR: Internal error\n");
                exit($return_code);

            default:
                break;

        }
    }

    public static function help_msg():void
    {
        echo("\n--------------------------------- parse.php ----------------------------------\n\n");
        echo(
            "\t     Script parse.php checks lexical and syntactic rules of
             IPPcode22 source code.
             This code is loaded from standard input and script returns XML
             representation of this source code.
             
             USAGE: php8.1 parse.php [--help] < srcCode.IPPcode22
             
             --help -> prints help to standard output
             \n");
        echo("--------------------------------------------------------------------------------\n\n");
        echo("----------------------------------- test.php -----------------------------------\n\n");
        echo("\t    Script test.php tests output of scripts (parse.php and 
            interpret.py) and compare them with given tests' outputs
            
            USAGE: php8.1 test.php [--help][--directory=path][--recursive][--parse-script=file][--int-script=file][--parse-only][--int-only][--jexampath=path][--noclean]
            
                         --help -> prints help to standard output (can't be combined with other parameters)
               --directory=path -> script will look for tests in given directory (if the parameter is missing, uses actual directory)
                    --recursive -> script will look for tests in all subdirectories of given directory
            --parse-script=file -> file with script in PHP 8.1 for analysis source code in IPPcode22 
                                    (if not given, script will use script parse.php in actual directory)
              --int-script=file -> file with script in Python 3.8 for interpret of XML representation of source code in IPPcode22
                                    ((if not given, script will use script interpret.py in actual directory)
                   --parse-only -> script will test only script for analysis of source code in IPPcode22
                                    (can't be combined with parameters --int-only and --int-script=file)
                     --int-only -> script will test only script for interpret of XML representation
                                    (can't be combined with parameters --parse-only, --parse-script=file and --jexampath=path)
               --jexampath=path -> path to directory with file jexamxml.jar, which contains JAR file with tool A7Soft JExamXML and config file options
                                    (if not given, script will use path /pub/courses/ipp/jexamxml/ on server Merlin)
                      --noclean -> while test.php is running, temporary files will NOT be deleted
                       \n");
        echo("--------------------------------------------------------------------------------\n\n");
        echo("--------------------------------- interpret.py ---------------------------------\n\n");
        echo("\t    Script interpret.py loads XML representation of source code in IPPcode22 and according to command line parameters
            interprets and generates output
            
            USAGE: python3 interpret.py [--help][--source=file][--input=file]
            
                   --help -> prints help to standard output (can't be combined with other parameters)
            --source=file -> input file with XML representation
             --input=file -> file with inputs for interpretation given source code
             \n");
        echo("--------------------------------------------------------------------------------\n\n");

        self::error_statement(RETURN_CODES::GREAT_SUCCESS);

    }
}

// Class with regular expression patterns
final class REGEX
{
    public const
        BOOL_TYPE = '(^(bool)@(false|true)$)',
        NIL_TYPE = '(^(nil)@(nil)$)',
        INT_TYPE = '(^(int)@[-+]?[0-9]+$)',
        STR_TYPE = '(^(string)@([^\s#\\\\]|\\\\\d{3})*$)',
        
        VAR_TYPE = '(^(LF|GF|TF)@[a-zA-Z_\-\#$&;%\*!\?][a-zA-Z0-9_\-\#$&;%\*!\?]*$)',
        LABEL_TYPE = '(^[a-zA-Z_\-\#$&;%\*!\?][a-zA-Z0-9_\-\#$&;%\*!\?]*$)',
        SYMB_TYPE = '(' . self::NIL_TYPE . '|' . self::BOOL_TYPE . '|' . self::INT_TYPE . '|' . self::STR_TYPE . '|' .self::VAR_TYPE. ')' ,
        TYPE = '(^(int|string|bool|nil)$)',

        DIR = '([-]{2}(directory=))',
        PARSE = '([-]{2}(parse-script=))',
        INTERPRET = '([-]{2}(int-script=))',
        JEXAM = '([-]{2}(jexampath=))';
}

//Class for creating XML document
final class XMLout
{
    private int $order_count = 1;
    private $doc;

/*
 * @return void
 * 
 * @brief XML representation document constructor
 * 
*/
    function __construct()
    {
        $this->doc = xmlwriter_open_memory();
        xmlwriter_set_indent($this->doc, 1);
        xmlwriter_start_document($this->doc, '1.0', 'UTF-8');
        xmlwriter_start_element($this->doc, "program");
        xmlwriter_write_attribute($this->doc, "language", "IPPcode22");
    }

/*
 * @param $order_type -> Name of order
 * @param $arg_num -> Number of arguments
 * @param $arg1 -> First argument (optional)
 * @param $arg2 -> Second argument (optional)
 * @param $arg3 -> Third argument (optional)
 * @return void
 * 
 * @brief Function creates XML representation of code 
 * 
*/
    public function write_doc($order_type, $arg_num, $arg1 = null, $arg2 = null, $arg3 = null)
    {
        xmlwriter_start_element($this->doc, "instruction");
        xmlwriter_write_attribute($this->doc, "order", $this->order_count);
        xmlwriter_write_attribute($this->doc, "opcode", strtoupper($order_type));

        // Opcode instruction with one argument
        if($arg_num >=1)
        {
            // Replacements of problematic characters
            $arg1 = preg_replace('/[&]/', "&amp;", $arg1);
            $arg1 = preg_replace('/[<]/', "&lt;", $arg1);
            $arg1 = preg_replace('/[>]/', "&gt;", $arg1);

            // Check if first argument is <var>
            if(preg_match(REGEX::VAR_TYPE, $arg1))
            {
                xmlwriter_start_element($this->doc,"arg1");
                xmlwriter_write_attribute($this->doc, "type", "var");
                xmlwriter_write_raw($this->doc, $arg1);
                xmlwriter_end_element($this->doc);
            }
            // Check if first argument is <label>
            elseif(preg_match(REGEX::LABEL_TYPE, $arg1))
            {
                xmlwriter_start_element($this->doc,"arg1");
                xmlwriter_write_attribute($this->doc, "type", "label");
                xmlwriter_write_raw($this->doc, $arg1);
                xmlwriter_end_element($this->doc);
            }
            else
            {
                $arg_split = explode('@', $arg1,2);
                xmlwriter_start_element($this->doc, "arg1");
                xmlwriter_write_attribute($this->doc, "type", $arg_split[0]);
                xmlwriter_write_raw($this->doc, $arg_split[1]);
                xmlwriter_end_element($this->doc);
            }
        }
        
        // Opcode instruction with two arguments
        if($arg_num >=2)
        {
            // Replacements of problematic characters
            $arg2 = preg_replace('/[&]/', "&amp", $arg2);
            $arg2 = preg_replace('/[<]/', "&lt", $arg2);
            $arg2 = preg_replace('/[>]/', "&gt", $arg2);

            // Check if second argument is <var>
            if(preg_match(REGEX::VAR_TYPE, $arg2))
            {
                xmlwriter_start_element($this->doc,"arg2");
                xmlwriter_write_attribute($this->doc, "type", "var");
                xmlwriter_write_raw($this->doc, $arg2);
                xmlwriter_end_element($this->doc);
            }
            // Check if second argument is <type>
            elseif(!preg_match("/(int@|string@|bool@|nil@)/", $arg2))
            {
                xmlwriter_start_element($this->doc,"arg2");
                xmlwriter_write_attribute($this->doc, "type", "type");
                xmlwriter_write_raw($this->doc, $arg2);
                xmlwriter_end_element($this->doc);
            }
            else
            {
                $arg_split = explode('@', $arg2,2);
                xmlwriter_start_element($this->doc, "arg2");
                xmlwriter_write_attribute($this->doc, "type", $arg_split[0]);
                xmlwriter_write_raw($this->doc, $arg_split[1]);
                xmlwriter_end_element($this->doc);
            }
        }

        // Opcode instruction with three arguments
        if($arg_num >=3)
        {
            // Replacements of problematic characters
            $arg3 = preg_replace('/[&]/', "&amp", $arg3);
            $arg3 = preg_replace('/[<]/', "&lt", $arg3);
            $arg3 = preg_replace('/[>]/', "&gt", $arg3);

            // Check if third argument is <var>
            if(preg_match(REGEX::VAR_TYPE, $arg3))
            {
                xmlwriter_start_element($this->doc,"arg3");
                xmlwriter_write_attribute($this->doc, "type", "var");
                xmlwriter_write_raw($this->doc, $arg3);
                xmlwriter_end_element($this->doc);
            }
            else
            {
                $arg_split = explode('@', $arg3,2);
                xmlwriter_start_element($this->doc, "arg3");
                xmlwriter_write_attribute($this->doc, "type", $arg_split[0]);
                xmlwriter_write_raw($this->doc, $arg_split[1]);
                xmlwriter_end_element($this->doc);
            }
        }

        xmlwriter_end_element($this->doc);
        $this->order_count++;
    }

/*
 * @return void
 * 
 * @brief Function prints XML representation of code
 * 
*/
    public function print_doc()
    {
        xmlwriter_end_element($this->doc);
        echo xmlwriter_output_memory($this->doc);
    }
}

final class Writer
{

    public function writeHtml($nameArray, $successArray, $directoryArray ,$test_num, $test_succeded)
    {
        fprintf(STDOUT,"<!DOCTYPE html>\n<html lang=\"cs\">\n<head>\n");

        fprintf(STDOUT, "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n");

        fprintf(STDOUT, "<style>\n");
        fprintf(STDOUT, "\t a{\n\ttext-decoration: none;\n\t color: black;\n\t font-weight: bold;\n\t} \n");
        fprintf(STDOUT, "\t li.error{\n\t color:red \n\t}\n");
        fprintf(STDOUT, "\t li.success{\n\t color:green \n\t}\n");
        fprintf(STDOUT, "\t #tests{\n\t background-color: linear-gradient(white,lightgray);\n\t overflow: scroll;\n\t padding: 20px;\n\t box-shadow: 0px 0px 10px #6d96e2;\n\t height: 70vh;\n\t width: 40%%;\n\t}\n");
        fprintf(STDOUT, "\t h4{\n\tcolor: blue;\n\t}\n");
        fprintf(STDOUT, "\t #stats{\n\tbackground-color: lightgray;\n\t margin:auto;\n\t border-radius: 20px;\n\t box-shadow: 0px 0px 10px #10e056;\n\t width: fit-content;\n\t}\n");
        fprintf(STDOUT, "\t #percentage{\n\tcolor: black;\n\t font-weight: bold;\n\t}\n");
        fprintf(STDOUT, "\t h3{\n\t color:black; \n\tfont-weight: bold;\n\t}\n");
        fprintf(STDOUT,"\t </style>\n");

        fprintf(STDOUT, "<title>IPP TEST RESULTS </title>\n");

        fprintf(STDOUT,"</head>\n");

        fprintf(STDOUT, "<body>\n");

        fprintf(STDOUT, "\t<div id=\"tests\">\n");
        for($i = 0; $i < sizeof($directoryArray); $i++)
        {
            fprintf(STDOUT, "\t\t<a>$directoryArray[$i]<a/>\n");         // Name of test directory
            $this->creatingNav($nameArray[$i], $successArray[$i]);      // List of test names
        }
        fprintf(STDOUT, "\t</div\n");

        fprintf(STDOUT, "\t<div id=\"stats\">\n");
        fprintf(STDOUT, "<h3>TEST SUCCEDED / TEST NUMBER</h3>");
        fprintf(STDOUT, "\t\t<h4>".$test_succeded."/".$test_num."</h4>\n");
        $stats = ($test_succeded/$test_num)*100; 
        fprintf(STDOUT, "\t\t<p id=\"percentage\">".$stats."%%</p>\n");
        fprintf(STDOUT, "\t</div\n");

        fprintf(STDOUT, "</body>\n");
        fprintf(STDOUT, "</html>\n");
    }

    public function creatingNav($list, $color)
    {
        fprintf(STDOUT,"\t<ul>\n");
        $i = 0;
        foreach($list as $current)
        {
            if($color[$i] == 0)
            {
                $class = "class=\"error\"";
            }
            else
            {
                $class = "class=\"success\"";
            }

            fprintf(STDOUT,"\t\t<li $class>$current</li>\n");
            $i++;
        }
        fprintf(STDOUT,"\t</ul>\n");
    }
}
 

?>