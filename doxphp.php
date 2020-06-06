<?php
/**
 * @copyright Copyright 2020 Silvio Sparapano <ssilvio@libero.it>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License
 * 
 * @file doxphp.php
 * This script is a general language filter for Doxygen documentation, useful for languages not supported by Doxygen.
 * It creates a php-like source code, starting from a DocBlock documented source file.  
 * Doesn't matter the programming language of the source file, the script analyze only the DocBlocks inside and create minimal source code declaration for Doxygen.
 * The output can be interpreted by Doxygen as standard PHP code.
 * 
 * **General info & limitations**  
 * Only `@class`, `@fn`, and `@var` Doxygen commands are managed by this script (with '@', not '\'!).
 * All other Doxygen command can exists into DocBlocks but will be ignored by the script (not by Doxygen).
 * The whole source code is *not* reported to the output.     
 * The output contains only the original DocBlocks, and below of each of them, one row representing the declaration of Class, Function or Variable to be documented.
 * Only documented section of the source file will be processed. 
 * DocBlocks must be defined by '/** ... *' multi-line sections (not "//").
 *
 * **Classes**  
 * Use the command `@class` followed by the class name.
 * The script will report to the output the DocBlock, followed by the class definition.
 * IMPORTANT: do not place other comments after `@class <className>`, on the same line. Use the following lines of the DocBlock.
 * 
 * **Functions**
 * Use the command `@fn` followed by the function name, including parameters. 
 * Example: @fn foo(bar)
 * This script will report to the output the DocBlock, followed by the function definition.
 * If the function belongs to a class, it's necessary to tell this to Doxygen by the command `@memberof <className>`.
 * IMPORTANT: do not place other comments after `@fn <functionName>`, on the same line. Use the following lines of the DocBlock.
 * 
 * **Variables**
 * Use the command `@var` followed by the variable name.
 * Example: @var foo
 * If the variable belongs to a class, it's necessary to tell this to Doxygen by the command `@memberof <className>`.
 * IMPORTANT: do not place other comments after `@var <variableName>`, on the same line. Use the following lines of the DocBlock.
 * 
 * **Doxygen Configuration**
 * From Doxygen configuration file (e.g. for javascript source code):
 * FILTER_PATTERNS        = *.js="php /doxphp.php"
 * 
 * @version 1.0.0
 */
$dox = new dox();
$dox->run($argv);

class dox 
{
    private $version = "1.0.0";
    private $errorMsg = "";
    private $inputFileName = "";
    private $params = array();

    private $cReset = "\033[0m";
    private $cWhite = "\033[37m";
    private $cRed = "\033[91m";
    private $cLight = "\033[97m";
    private $cGrey = "\033[90m";

    function run($params) {
        $this->params = $params;
        if($this->parseParameters($this->params)){
            if(strlen($this->inputFileName)>0){
                $inputFile = @fopen($this->inputFileName, "r");
                if($inputFile){
                    $this->parseFile($inputFile);
                }else{
                    $this->errorMsg = "Unable to read input file: ".$this->inputFileName;
                    $this->showErrors();
                }
            }
        }else{
            $this->showErrors();
        }
    }

    function parseParameters($args){
        $ret = false;
        $this->errorMsg = "";
        $this->inputFileName = "";
        $nArgs = count($args);
        if($nArgs>1){
            if(($args[1]=='--manual') || ($args[1]=='-m')){
                $this->showMan();
            }elseif(($args[1]=='--help') || ($args[1]=='-h')){
                $this->showHelp();
            }elseif(($args[1]=='--version') || ($args[1]=='-v')){
                $this->showVersion();
            }else{
                $this->inputFileName = $args[1];
            }
            $ret = true;
        }else{
            $this->errorMsg = "Insufficient parameters";
        }
        return $ret;
    }
    
    
    function parseFile($handle) : bool {
        $ret = false;
        $toc = array();
        $count = 0;
        $blockOpened = false;
        $block = "";
        $bStart = 0;
        $bLen = 0;
    
        if($handle){
            while (($line = fgets($handle, 4096)) !== false) {
                $count++;
                //printf("%06d %s", $count, $line);
                if(strpos($line, "/**") !== false){
                    //printf("%06d>%s", $count, $line);
                    $blockOpened = true;
                    $bStart = $count;
                    $bLen = 0;
                }
                if($blockOpened){
                    $bLen ++;
                    $block .= $line;
                    if(strpos($line, "*/") !== false){
                        //printf("%06d<%s", $count, $line);
                        array_push($toc, array("bStart"=>$bStart, "bLen"=>$bLen, "bk"=>$block, "fn"=>$this->parseBlock($block)) );
                        $blockOpened = false;
                        $block = "";
                        $bStart = 0;
                        $bLen = 0;
                    }
                }
    
            }
            if (!feof($handle)) {
                echo "Error: unexpected fgets() fail\n";
            }
            fclose($handle);
        }
        //echo print_r($toc, true);
        $this->createOutput($toc);
        return $ret;
    }
    
    function parseBlock($b) {
        $p = $this->searchForTok($b, '@class ');
        if($p!==false){
            return "class " . $p . "{}";
        }
        $p = $this->searchForTok($b, '@fn ');
        if($p!==false){
            return "function " . $p . "{}";
        } 
        $p = $this->searchForTok($b, '@var ');
        if($p!==false){
            return "var " . $p . ";";
        } 
    }
    
    function searchForTok($b, $tok) {
        $b = trim($b);
        $p = (strpos($b, $tok));
        if( $p !== false ){
            $eol = strpos($b, "\n", $p);
            $diff = $eol-($p+strlen($tok));
            return trim(substr($b, $p+strlen($tok), $diff));
        }else{
            return false;
        }
    }
    
    function createOutput($r){
        $s = "";
        $l = 0;
        foreach($r as $b){
            while($l<$b["bStart"]){
                $s .= PHP_EOL;
                $l++;
            }
            $s .= $b['bk'];
            $s .= $b['fn'].PHP_EOL;
            $l = $l + $b["bLen"] + 1;
        }
        echo $s;
    }

    function showErrors(){
        if(strlen($this->errorMsg)>0){
            printf(PHP_EOL.$this->cRed."Error: %s".$this->cReset.PHP_EOL, $this->errorMsg);
        }
        $this->showHelp();
    }

    function showVersion(){
        echo $this->version;
    }
    function showHelp(){
        printf(PHP_EOL);
        printf($this->cWhite."Usage:".$this->cReset.PHP_EOL);
        printf("    ".$this->cLight."> php doxyphp.php <input_file>".$this->cReset.PHP_EOL);
        printf("    ".$this->cLight."> php doxyphp.php <options>".$this->cReset.PHP_EOL);
        printf(PHP_EOL);
        printf($this->cWhite."Options:".$this->cReset.PHP_EOL);
        printf(PHP_EOL);
        printf("    ".$this->cLight."-h, --help     Show usage".$this->cReset.PHP_EOL);
        printf("    ".$this->cLight."-m, --manual   Show manual".$this->cReset.PHP_EOL);
        printf("    ".$this->cLight."-v, --version  Show version".$this->cReset.PHP_EOL);
        printf(PHP_EOL);
    }
    
    function showMan(){
        $k = 55;
        echo 
<<<MAN
   $this->cWhite NAME $this->cGrey
        doxphp.php
        This script is a general language filter for Doxygen documentation, useful for languages not supported by Doxygen.
    
   $this->cWhite USAGE
       $this->cLight > php doxyphp.php <input_file> $this->cGrey
   
   $this->cWhite DESCRIPTION $this->cGrey
        This script creates a php-like source code, starting from a DocBlock documented source file.  
        Doesn't matter the programming language of the source file, the script analyze only the DocBlocks inside and create minimal source code declaration for Doxygen.
        The output can be interpreted by Doxygen as standard PHP code.
    
   $this->cWhite GENERAL INFO & LIMITATIONS $this->cGrey
        Only `@class`, `@fn`, and `@var` Doxygen commands are managed by this script (with '@', not '\'!).
        All other Doxygen command can exists into DocBlocks but will be ignored by the script (not by Doxygen).
        The whole source code is *not* reported to the output.     
        The output contains only the original DocBlocks, and below of each of them, one row representing the declaration of Class, Function or Variable to be documented.
        Only documented section of the source file will be processed. 
        DocBlocks must be defined by '/** ... */' multi-line sections (not "//").
    
       $this->cWhite **Classes** $this->cGrey
        Use the command `@class` followed by the class name.
        The script will report to the output the DocBlock, followed by the class definition.
        IMPORTANT: do not place other comments after `@class <className>`, on the same line. Use the following lines of the DocBlock.
    
       $this->cWhite **Functions** $this->cGrey
        Use the command `@fn` followed by the function name, including parameters. 
        Example: @fn foo(bar)
        This script will report to the output the DocBlock, followed by the function definition.
        If the function belongs to a class, it's necessary to tell this to Doxygen by the command `@memberof <className>`.
        IMPORTANT: do not place other comments after `@fn <functionName>`, on the same line. Use the following lines of the DocBlock.
    
       $this->cWhite **Variables** $this->cGrey
        Use the command `@var` followed by the variable name.
        Example: @var foo
        If the variable belongs to a class, it's necessary to tell this to Doxygen by the command `@memberof <className>`.
        IMPORTANT: do not place other comments after `@var <variableName>`, on the same line. Use the following lines of the DocBlock.
    
       $this->cWhite **Doxygen Configuration** $this->cGrey
        From Doxygen configuration file (e.g. for javascript source code):
        FILTER_PATTERNS        = *.js="php /doxphp.php"
$this->cReset    
MAN;
    }



}
