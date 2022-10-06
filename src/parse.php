<?php


/*
 * @AUTHOR  : Denis Horil
 * @LOGIN   : xhoril01
 * @EMAIL   : xhoril01@stud.fit.vutbr.cz
 * @PROJECT : VUT IPP Projekt 1 - Analyzator kodu v IPPcode22
 * 
 * 
 * Main script parse.php for lexical and syntactic analysis of source code in IPPcode22
 * 
*/


require 'classes.php';

/*
 * @param $string -> string to be checked
 * @return bool
 * 
 * @brief Function checks if the parameter is <var>
 * 
*/
function is_var($string):bool
{
    $pattern = REGEX::VAR_TYPE;;
    return preg_match($pattern, $string);
}

/*
 * @param $string -> string to be checked
 * @return bool
 * 
 * @brief Function checks if the parameter is <label>
 * 
*/
function is_label($string):bool
{
    $pattern = REGEX::LABEL_TYPE;
    return preg_match($pattern, $string);
}

/*
 * @param $string -> string to be checked
 * @return bool
 * 
 * @brief Function checks if the parameter is <symb> 
 * 
*/
function is_symb($string):bool
{
    $pattern = REGEX::SYMB_TYPE;
    return preg_match($pattern, $string);
}

/*
 * @param $string -> string to be checked
 * @return bool
 * 
 * @brief Function checks if the parameter is <type> 
 * 
*/
function is_type($string):bool
{
    $pattern = REGEX::TYPE;
    return preg_match($pattern, $string);
}

/*
 * @param $splitted_arr -> array with result of splitted line
 * @param $doc -> name of XML file
 * @param $line_num -> number of line in input file
 * @return void
 * 
 * @brief Function divides parameters according to number of  arguments
 *        and sends information about frames and their arguments to 
 *        XML documentation generator
 * 
*/
function frame_switch(array $splitted_arr, $doc, $line_num):void
{
    //Empty array
    if($splitted_arr == null) return;

    // Dividing according to number of arguments
    switch(strtoupper($splitted_arr[0]))
    {   
        // Without arguments
        case "CREATEFRAME":
        case "PUSHFRAME":
        case "POPFRAME":
        case "RETURN":
        case "BREAK":

            // Checking number of arguments
            if(count($splitted_arr) != 1)
            {
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num);
            }

            $doc->write_doc($splitted_arr[0], 0);
            break;
        
        // 1 argument -> <var>
        case "DEFVAR":
        case "POPS": 
            
            // Checking number of arguments
            if(count($splitted_arr) != 2)
            {
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num);
            }
            
            // Checking the right type of arguments
            if(!is_var($splitted_arr[1]))
            {
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num);
            }

            $doc->write_doc($splitted_arr[0],1,$splitted_arr[1]);
            break;
        
        // 1 argument -> <label>
        case "CALL":
        case "LABEL":
        case "JUMP":

            // Checking number of arguments
            if(count($splitted_arr) != 2)
            {
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num);
            }

            // Checking the right type of arguments
            if(!is_label($splitted_arr[1]))
            {
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num);
            }

            $doc->write_doc($splitted_arr[0],1,$splitted_arr[1]);
            break;
        
        // 1 argument -> <symb>
        case "PUSHS":
        case "WRITE":
        case "EXIT":
        case "DPRINT":

            // Checking number of arguments
            if(count($splitted_arr) != 2)
            {
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num);
            }
            
            // Checking the right type of arguments
            if(!is_symb($splitted_arr[1]))
            {
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num);
            }
            
            $doc->write_doc($splitted_arr[0],1,$splitted_arr[1]);
            break;

        // 2 arguments -> <var> <symb>
        case "MOVE":
        case "STRLEN":
        case "TYPE":
        case "INT2CHAR":
        case "NOT": 
            
            // Checking number of arguments
            if(count($splitted_arr) != 3)
            {
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num); 
            }

            // Checking the right type of arguments
            if(!is_var($splitted_arr[1]) || !is_symb($splitted_arr[2]))
            {   
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num);
            }

            $doc->write_doc($splitted_arr[0],2,$splitted_arr[1],$splitted_arr[2]);
            break;

        // 2 arguments -> <var> <type>
        case "READ":
            
            // Checking number of arguments
            if(count($splitted_arr) != 3)
            {
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num); 
            }

            // Checking the right type of arguments
            if(!is_var($splitted_arr[1]) || !is_type($splitted_arr[2]))
            {
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num);
            }

            $doc->write_doc($splitted_arr[0],2,$splitted_arr[1],$splitted_arr[2]);
            break;

        // 3 arguments -> <var> <symb1> <symb2>
        case "ADD":
        case "SUB":
        case "IDIV":
        case "MUL":
        case "LT":
        case "GT":
        case "EQ":
        case "CONCAT":
        case "AND":
        case "OR":
        case "STRI2INT":
        case "GETCHAR":
        case "SETCHAR":
            
            // Checking number of arguments
            if(count($splitted_arr) != 4)
            {
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num); 
            }

            // Checking the right type of arguments
            if(!is_var($splitted_arr[1]) || !is_symb($splitted_arr[2]) || !is_symb($splitted_arr[3]))
            {
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num);
            }

            $doc->write_doc($splitted_arr[0],3,$splitted_arr[1],$splitted_arr[2],$splitted_arr[3]);
            break;

        // 3 arguments -> <label> <symb1> <symb2>
        case "JUMPIFEQ":
        case "JUMPIFNEQ": 

            // Checking number of arguments
            if(count($splitted_arr) != 4)
            {
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num); 
            }

            // Checking the right type of arguments
            if(!is_label($splitted_arr[1]) || !is_symb($splitted_arr[2]) || !is_symb($splitted_arr[3]))
            {
                EXITUS::error_statement(RETURN_CODES::PARSE_ERROR, $line_num);
            }

            $doc->write_doc($splitted_arr[0],3,$splitted_arr[1],$splitted_arr[2],$splitted_arr[3]);
            break;        

        default: EXITUS::error_statement(RETURN_CODES::UNKNOWN_OPCODE, $line_num);
    }
}

//--------------------------------------- MAIN --------------------------------------------//

// Chcecking arguments of command line
if($argc <= 2)
{
    if($argc == 2)
    {
        if($argv[1] == "--help")
        {
            EXITUS::help_msg();
        }
        else
        {
            // Invalid combination of parameters
            EXITUS::error_statement(RETURN_CODES::MISSING_PARAMETER);
        }
    } 
}
else 
{
    // Unknown parameters
    EXITUS::error_statement(RETURN_CODES::MISSING_PARAMETER);
}

// Variables
$is_header = false;
$line_num = 0;
$doc = new XMLout;

// Reading lines from file
while($line = fgets(STDIN))
{
    $line_num++;
    
    //Deleting commentary
    $commentary = explode("#", $line);
    $without_commentary = $commentary[0];

    //Deleting whitespaces
    $trimmed = trim($without_commentary);

    // Line splitted to frame and variables
    $split_2_frame = preg_split("/ /",$trimmed,-1, PREG_SPLIT_NO_EMPTY);

    //Header check
    if(!$is_header && $split_2_frame )
    {
        if($split_2_frame[0] == ".IPPcode22")
        {
            $is_header = true;
        }
        else EXITUS::error_statement(RETURN_CODES::MISSING_HEADER);
    }
    else 
    {
        frame_switch($split_2_frame, $doc, $line_num);
    } 
}

if(!$is_header)
{
    EXITUS::error_statement(RETURN_CODES::MISSING_HEADER);
}

$doc->print_doc();

EXITUS::error_statement(RETURN_CODES::GREAT_SUCCESS);

//----------------------------------- END OF MAIN -----------------------------------------//




/*
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWWWNNWXKXXNWWMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWN0xdllc::ccclxk0XWMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWXkdlc:::,........':okKNWMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMN0kkO0KKKKkdoc;'. ...';cdONMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMNK0KXWMMMWXOkkkO0KKKKXNNX0o;'......;xXWMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMXxddk0WMWXkoll:coollldkO0KKOo:,.....,oOXWMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMW0dx0NNXk:';lolcc:;,.,:lxO00x;...  ..':xKWMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMWKk0NWNKx, .',;::cclllodkO000xc,...  .';lONMMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMWK00KXNXKkc,',;:clodkOOO00KKKXXKxc,. ...'cONMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMNK00000KKOxo:;:clodxkkOOO0000KXXKOxl;,,,:xKWMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWXK00KKK0OkdlcclloddxxkkOO00KXKKXXX0xoc;lONMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWNXXXXK00kxdoooddoooddxkOO0KKKXNNXXXK0xdkXWMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWWNK000OkkxxkkxxxdddxxkkO0KKXNNXKOO0K0kOXMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWWXK00OOkkkOkkkOOOkkkkkOO0KXXNNXkddxO0OkKWMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWNX0OOkkkkkkkkOO000000000KXXNNNKkoloxO00NMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWXK0Okkkkkkkkxk0KKKKKKKKKKXXXXXK0koldO0KWMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMNXK00OkkkOOOOkO0KKKXXXXXXXXXXXXKOkkOkO0XWMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWNXKK0OOkkkkkkkkO000XXXXNNXXNNNNKkdddooxKWMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWXKK0OOkkxxkkOOOOO0KXXXNNWWWWNXX0xlcccdKWMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWX00OOkkkkkOOO0KKKKXXXXNWWNXK0KK0x:':xXMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMNK0OOkxxxkkkO0KKKKKXNNNNXK0O0XXXKd;ckNMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWXKOOkxxxxkO000KKKXXNNNXK000KXNXXX0OXWMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWNNKOkkkkkkOO000KKKXXXXKK00O0KXXXXXXXNMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWNXXKOxxxxkkOO0000000000OOOOOO0KXXXXXNWMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWXK0OxdllodkO0OOOOOkkxdoodxkO0KKXXXXWWMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWXKOkxoc::ccoxxdolcc:;,;:ldxkO0KKXNWMMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWNK0OxdddooxOOkkOOOxkOOOOkkO0KXNWMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM
MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMWWNNXKKKKKKXWMMMMMMMMMMMWK0KXNWWMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM
*/

?>