<?php

/*
 * @AUTHOR  : Denis Horil
 * @LOGIN   : xhoril01
 * @EMAIL   : xhoril01@stud.fit.vutbr.cz
 * @PROJECT : VUT IPP Projekt 2 - Testovaci ramec
 * 
 * 
 * Testing script for parse.php and interpret.py
 * 
*/

include 'classes.php';

// Testing output from parse.php
function parse_test($dir, $parse_dir, $jexam_dir, $int_dir, $recursive, $parse_only, $noclean, &$test_succeded, &$test_num, &$nameArray, &$successArray, &$directoryArray)
{
    $files = scandir($dir);
    if(!$files)
    {
        EXITUS::error_statement(RETURN_CODES::TEST_ERROR);
    }

    // Recursive finding of tests in subdirectories
    if($recursive)
    {
        for($i = 0; $i < count($files); $i++)
        {
            if($files[$i] == "." || $files[$i] == "..") continue;

            if(is_dir($dir."/".$files[$i]))
            {
                $tmp_dir = $dir ."/". $files[$i];
                parse_test($tmp_dir, $parse_dir, $jexam_dir, $int_dir, $recursive, $parse_only, $noclean, $test_succeded, $test_num,$nameArray,$successArray,$directoryArray);
            }
        }
    }

    $testArray = array();
    $retArray = array();

    array_push($directoryArray, $dir);

    for($i = 0; $i < count($files); $i++)
    {
        if($files[$i] == "." || $files[$i] == "..") continue;

        if(preg_match('/(.src)$/',$files[$i]))
        {   
            // Name of test file in actual subdirectory
            $name = preg_replace('/(.src)$/', "", $files[$i]);
            array_push($testArray, $name);

            // Creating filenames depending on name of source file
            $in_filename = preg_replace('/(.src)$/', ".in", $files[$i]);
            $out_filename = preg_replace('/(.src)$/', ".out", $files[$i]);
            $rc_filename = preg_replace('/(.src)$/', ".rc", $files[$i]);
    
            // Creating/opening files
            $in_file = valid_file($files, $in_filename, $dir);
            $out_file = valid_file($files, $out_filename, $dir);
            $rc_file = valid_file($files, $rc_filename, $dir);
            
            // Output from the script
            $my_output = $dir. "/parse_out-".$out_filename;

            // Command to be executed
            $cmd = "php8.1 ".$parse_dir. "<". $dir."/".$files[$i]. "> ". $my_output;

            // Execution
            $tmp = null;
            $return_val = null;
            exec($cmd, $tmp, $return_val);

            // Wanted return code
            $rc_val = fgets($rc_file);

            // Comparing output values and return code is not 0
            if($rc_val == $return_val && $rc_val)
            {
                $test_num++;
                $test_succeded++;
            } 
            // Comparing output values and return code is 0
            elseif($rc_val == $return_val && !$rc_val)
            {
                if($parse_only)
                {
                    $success = jexam($dir,$jexam_dir,$my_output, $out_filename);
                    if($success)
                    {
                        array_push($retArray, 1);
                        $test_num++;
                        $test_succeded++;
                    }
                    else
                    {
                        $test_num++;
                        array_push($retArray, 0);
                    }
                }

                else
                {
                    $success = both($dir, $int_dir, $my_output, $in_filename, $out_filename, $rc_val,$noclean);
                    if($success)
                    {
                        array_push($retArray, 1);
                        $test_num++;
                        $test_succeded++;
                    }
                    else
                    {
                        array_push($retArray, 0);
                        $test_num++;
                    }
                }
            }
            // Return code from interpret
            elseif($rc_val != $return_val && !$return_val)
            {
                if(!$parse_only)
                {
                    $success = both($dir, $int_dir,$my_output, $in_filename, $out_filename, $rc_val, $noclean);
                    if($success)
                    {
                        array_push($retArray, 1);
                        $test_num++;
                        $test_succeded++;
                    }
                    else
                    {
                        $test_num++;
                        array_push($retArray, 0);
                    }
                }
                else
                {
                    $test_num++;
                    array_push($retArray, 0);
                }   
            }
            else
            {
                $test_num++;
                array_push($retArray, 0);
            }
            
            // Closing files
            fclose($in_file);
            fclose($out_file);
            fclose($rc_file);

            // Removing temporary files
            if(!$noclean)
                unlink($my_output);
        }
    }

    array_push($nameArray, $testArray);
    array_push($successArray, $retArray);
}

// Comparing referential output and script output using A7Soft JExamXML
function jexam($dir,$jexam_dir,$my_output, $out_filename)
{
    $cmd = "java -jar ".$jexam_dir."jexamxml.jar ". $my_output. " ". $dir. "/". $out_filename;

    $jexam_out = null;
    $jexam_val = null;
    exec($cmd, $jexam_out, $jexam_val);

    if($jexam_out[count($jexam_out)-1] == "Two files are identical")
    {
        return TRUE;
    }   
    else 
    {
        return FALSE;
    }
}

// Using both scripts parse.php and interpret.py - result from parse.php willbe uses as source file in interpret.py
function both($dir, $int_dir, $src, $in_filename, $out_filename, $rc_val, $noclean)
{
    // Output from the script
    $my_output = $dir. "/int_out-".$out_filename;

    // Command to be executed
    $cmd = "python3 ".$int_dir. " --source=".$src." --input=".$dir."/".$in_filename.">". $my_output;

    $tmp = null;
    $return_val = null;
    exec($cmd, $tmp, $return_val);
    // Comparing output values and return code is not 0
    if($rc_val == $return_val && $rc_val)
    {
        if(!$noclean)
        {
            unlink($my_output);
        }
        return TRUE;
    }

    // Comparing output values and return code is 0
    elseif($rc_val == $return_val && !$rc_val)
    {
        $cmd = "diff ".$my_output." ".$dir."/".$out_filename;

        $tmp = null;
        $return_val = null;
        exec($cmd, $diff, $return_val);

        if(!$noclean)
        {
            unlink($my_output);
        }

        if(count($diff) == 0)
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }
    // Failed test
    else
    {
        if(!$noclean)
        {
            unlink($my_output);
        }
        return FALSE;
    }
}

// Tetsing output just from interpret.py
function int_test($dir, $int_dir, $recursive, $noclean, &$test_succeded, &$test_num, &$nameArray, &$successArray,&$directoryArray)
{
    $files = scandir($dir);
    if(!$files)
    {
        EXITUS::error_statement(RETURN_CODES::TEST_ERROR);
    }

    if($recursive)
    {
        for($i = 0; $i < count($files); $i++)
        {
            if($files[$i] == "." || $files[$i] == "..") continue;

            if(is_dir($dir."/".$files[$i]))
            {
                $tmp_dir = $dir ."/". $files[$i];
                int_test($dir, $int_dir, $recursive, $noclean, $test_succeded, $test_num,$nameArray,$successArray,$directoryArray);
            }
        }
    }

    $testArray = array();
    $retArray = array();

    array_push($directoryArray, $dir);

    for($i = 0; $i < count($files); $i++)
    {
        if($files[$i] == "." || $files[$i] == "..") continue;

        if(preg_match('/(.src)$/',$files[$i]))
        {
            $name = preg_replace('/(.src)$/', "", $files[$i]);
            array_push($testArray, $name);

            // Creating filenames depending on name of source file
            $in_filename = preg_replace('/(.src)$/', ".in", $files[$i]);
            $out_filename = preg_replace('/(.src)$/', ".out", $files[$i]);
            $rc_filename = preg_replace('/(.src)$/', ".rc", $files[$i]);
    
            // Creating/opening files
            $in_file = valid_file($files, $in_filename, $dir);
            $out_file = valid_file($files, $out_filename, $dir);
            $rc_file = valid_file($files, $rc_filename, $dir);
            
            // Output from the script
            $my_output = $dir. "/int_out-".$out_filename;

            // Command to be executed
            $cmd = "python3 ".$int_dir. " --source=".$dir."/".$files[$i]." --input=".$dir."/".$in_filename.">". $my_output;

            // Execution
            $tmp = null;
            $return_val = null;
            exec($cmd, $tmp, $return_val);

            // Wanted return code
            $rc_val = fgets($rc_file);

            // Comparing output values and return code is not 0
            if($rc_val == $return_val && $rc_val)
            {
                array_push($retArray, 1);
                $test_num++;
                $test_succeded++;
            }

            // Comparing output values and return code is 0
            elseif($rc_val == $return_val && !$rc_val)
            {
                $cmd = "diff ".$my_output." ".$dir."/".$out_filename;

                $tmp = null;
                $return_val = null;
                exec($cmd, $diff, $return_val);
                
                if(count($diff) == 0)
                {
                    array_push($retArray, 1);
                    $test_num++;
                    $test_succeded++;
                }
                else
                {
                    array_push($retArray, 0);
                    $test_num++;
                }
            }
            // Fail
            else
            {
                array_push($retArray, 0);
                $test_num++;
            }

            fclose($in_file);
            fclose($out_file);
            fclose($rc_file);

            if(!$noclean)
                unlink($my_output);
        }
    }
    array_push($nameArray, $testArray);
    array_push($successArray, $retArray);
}

// Checks file validality, return file handlers
function valid_file($files, $name, $dir)
{
   for($i = 0; $i < count($files); $i++)
   {
        if(is_file($dir."/".$name))
            return fopen($dir."/".$name, 'c+');
   }

   if(preg_match('/[.rc]$/', $name))
   {
       $rc = fopen($dir."/".$name, 'w+');
       fprintf($rc, "0");
       fclose($rc);
   }
   
   return fopen($dir."/".$name, 'c+');
}

// -------------------------- MAIN ------------------------------ //
function main($argc, $argv)
{
    $test_succeded = 0;
    $test_num = 0;

    $dir = "./";
    $parse_dir = "./parse.php";
    $int_dir = "./interpret.py";
    $jexam_dir = "/pub/courses/ipp/jexamxml/";

    $recursive = false;
    $parse_only = false;
    $int_only = false;
    $noclean = false;

    $is_interpret = false;
    $is_parse = false;
    $is_jexam = false;

    for($i = 1; $i < $argc; $i++)
    {
        switch($argv[$i])
        {
            case "--help":
                if($argc > 2)
                {
                    EXITUS::error_statement(RETURN_CODES::MISSING_PARAMETER);
                }
                else
                {
                    EXITUS::help_msg();
                }

            case "--recursive":
                $recursive = true;
                continue 2;

            case "--parse-only":
                $parse_only = true;
                continue 2;

            case "--int-only":
                $int_only = true;
                continue 2;

            case "--noclean":
                $noclean = true;
                continue 2;
            
            default: 
            {
                if(preg_match(REGEX::DIR, $argv[$i]))
                {
                    $dir = preg_replace(REGEX::DIR, "", $argv[$i]);
                    continue 2;
                }
                elseif(preg_match(REGEX::PARSE, $argv[$i]))
                {
                    $parse_dir = preg_replace(REGEX::PARSE, "", $argv[$i]);
                    $is_parse = true;
                    continue 2;
                }
                elseif(preg_match(REGEX::INTERPRET, $argv[$i]))
                {
                    $int_dir = preg_replace(REGEX::INTERPRET, "", $argv[$i]);
                    $is_interpret = true;
                    continue 2;
                }
                elseif(preg_match(REGEX::JEXAM, $argv[$i]))
                {
                    $jexam_dir = preg_replace(REGEX::JEXAM, "", $argv[$i]);
                    $is_jexam = true;
                    continue 2;
                }
                else
                {
                    EXITUS::error_statement(RETURN_CODES::MISSING_PARAMETER);
                }
            }          
        }
    }

    if($parse_only)
    {
        if($is_interpret || $int_only)
        {
            EXITUS::error_statement(RETURN_CODES::MISSING_PARAMETER);
        }
    }

    if($int_only)
    {
        if($is_jexam || $is_parse || $parse_only)
        {
            EXITUS::error_statement(RETURN_CODES::MISSING_PARAMETER);
        }
    }


    $nameArray = array();
    $successArray = array();
    $directoryArray = array();

    if($parse_only || !$int_only)
    {
        parse_test($dir, $parse_dir, $jexam_dir, $int_dir, $recursive, $parse_only, $noclean, $test_succeded, $test_num, $nameArray,$successArray,$directoryArray);
    }
    elseif($int_only)
    {
        int_test($dir, $int_dir, $recursive, $noclean, $test_succeded, $test_num, $nameArray, $successArray,$directoryArray);
    }

    $html = new Writer();
    $html->writeHtml($nameArray, $successArray, $directoryArray, $test_num, $test_succeded);
   
}

main($argc,$argv)
?>