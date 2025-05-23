<?php

class ExampleClass
{
    function exampleFunction() {}

}

    $o = <<<EOF
    this is some text
    this is some text
EOF;

	$correctVar = false;
    $correctVar = true;

// Indent with spaces is not allowed
 $hello = array();
   $world = '';
// here the indention is mixed with tabs and spaces
// [tab][space][space][space][tab]return "<$name$xmlns$type_str $atts xsi:nil=\"true\"/>";
	   	return "<$name$xmlns$type_str $atts xsi:nil=\"true\"/>";
// [space][space][space][tab]return "<$name$xmlns$type_str $atts xsi:nil=\"true\"/>";
   	return "<$name$xmlns$type_str $atts xsi:nil=\"true\"/>";
// Doc comments are indent with tabs and one space
//[tab]/**
//[tab][space]*
	/**
	 * CVS revision for HTTP headers.
     *
	  * @var string
	 * @access private
	 */
	/**
     *
	*/

$str = 'hello
        there';

/**
 * This PHP DocBlock should be fine, even though there is a single space at the beginning.
 *
 * @var int $x
 */
$x = 1;

?>
<html>
    <head>
		<title>Foo</title>
	</head>
	<body>
		<div>
    		<div>
				<div>
	    		</div>
    	    </div>
        </div>
    </body>
</html>

<?php

		    // Issue #1404
        	// This is a line with mixed tabs and spaces.
	    	echo 'And here is another line with mixed tabs and spaces.';
	        echo 'And another one.';
    	    echo 'And another one.';
        	echo 'And another one.';

// Spaces after comment.
    
$x = 1;

// Mixed tabs and spaces after comment.
	    
$x = 1;

// Mixed spaces and tabs after comment.
    		   
$x = 1;

/*
 * This multi-line comment should be fine and should be ignored for metrics.
 */

/*
 This multi-line comment should be fine and should be ignored for metrics.

 Another line.
 */

	/**
	 * This PHP DocBlock is indented with tabs + one space; metrics should say tabs.
	 *
	 * @var int $x
	 */

	/*
	 * This multi-line comment is indented with tabs + one space; metrics should say tabs.
	 */

    /**
     * This PHP DocBlock is indented with spaces and should be fixed.
     *
     * @var int $x
     */

    /*
     * This multi-line comment is indented with spaces and should be fixed.
     */

    /*
     This multi-line comment is indented with spaces and should be fixed.

     Another line.
     */

// A `yield from` can be multiline and may contain spaces in the indentation whitespace between the keywords.
function myGenerator() {
	yield
    from
	gen2();
}
