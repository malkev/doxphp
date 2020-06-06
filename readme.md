# doxphp.php
This script is a general language filter for Doxygen documentation, useful for languages not supported by Doxygen.
## NAME ##
	doxphp.php

## DESCRIPTION ##
This script creates a php-like source code, starting from a DocBlock documented source file.  
Doesn't matter the programming language of the source file, the script analyze only the DocBlocks inside and create minimal source code declaration for Doxygen.
The output can be interpreted by Doxygen as standard PHP code.

## GENERAL INFO & LIMITATIONS ##
Only `@class`, `@fn`, and `@var` Doxygen commands are managed by this script (with `'@'`, not `'\'`!).
All other Doxygen command can exists into DocBlocks but will be ignored by the script (not by Doxygen).	
The whole source code is *not* reported to the output.     
The output contains only the original DocBlocks, and below of each of them, one row representing the declaration of Class, Function or Variable to be documented.	
Only documented section of the source file will be processed.	
DocBlocks must be defined by `'/** ... */'` multi-line sections (not `"//"`).

### Classes ###
Use the command `@class` followed by the class name.
The script will report to the output the DocBlock, followed by the class definition.   
**IMPORTANT:** do not place other comments after `@class <className>`, on the same line. Use the following lines of the DocBlock.

### Functions ###
Use the command `@fn` followed by the function name, including parameters. 
>Example: @fn foo(bar)

This script will report to the output the DocBlock, followed by the function definition.	
If the function belongs to a class, it's necessary to tell this to Doxygen by the command `@memberof <className>`.	
**IMPORTANT:** do not place other comments after `@fn <functionName>`, on the same line. Use the following lines of the DocBlock.

### Variables ###
Use the command `@var` followed by the variable name.
>Example: @var foo

If the variable belongs to a class, it's necessary to tell this to Doxygen by the command `@memberof <className>`.	
**IMPORTANT:** do not place other comments after `@var <variableName>`, on the same line. Use the following lines of the DocBlock.

### Doxygen Configuration ###
From Doxygen configuration file (e.g. for javascript source code):
>`FILTER_PATTERNS        = *.js="php /doxphp.php"`

## License
License [Aapache-2.0](https://github.com/malkev/pamp/LICENSE).

Your contribution will be appreciate:
[![Donate](https://www.paypalobjects.com/en_US/IT/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=UZAJWKUNPLHAG&currency_code=EUR&source=url)
