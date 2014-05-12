<?php


/**
* Language string extractor
*
* @package       cake
* @subpackage    cake.cake.console.libs.tasks
*/

if (!class_exists('File')) {
	require 'file.php';
}
if (!class_exists('Folder')) {
	require 'folder.php';
}


  class ExtractTask {

    /**
    * Paths to use when looking for strings
    *
    * @var string
    * @access private
    */
    var $__paths = array();
    var $params = array();

    /**
    * Files from where to extract
    *
    * @var array
    * @access private
    */
    var $__files = array();

    /**
    * Merge all domains string into the default.pot file
    *
    * @var boolean
    * @access private
    */
    var $__merge = false;

    /**
    * Current file being processed
    *
    * @var string
    * @access private
    */
    var $__file = null;

    /**
    * Contains all content waiting to be write
    *
    * @var string
    * @access private
    */
    var $__storage = array();

    /**
    * Extracted tokens
    *
    * @var array
    * @access private
    */
    var $__tokens = array();

    /**
    * Extracted strings
    *
    * @var array
    * @access private
    */
    var $__strings = array();

    /**
    * Destination path
    *
    * @var string
    * @access private
    */
    var $__output = null;
  
  
    /**
    * Standard input stream.
    *
    * @var filehandle
    * @access public
    */
    var $stdin;

    /**
    * Standard output stream.
    *
    * @var filehandle
    * @access public
    */
    var $stdout;

    /**
    * Standard error stream.
    *
    * @var filehandle
    * @access public
    */
    var $stderr;
  
  
  
  
    /**
    * Prompts the user for input, and returns it.
    *
    * @param string $prompt Prompt text.
    * @param mixed $options Array or string of options.
    * @param string $default Default input value.
    * @return Either the default value, or the user-provided input.
    * @access public
    */
    function getInput($prompt, $options = null, $default = null) {
      if (!is_array($options)) {
        $printOptions = '';
      } else {
        $printOptions = '(' . implode('/', $options) . ')';
      }

      if ($default === null) {
        $this->stdout($prompt . " $printOptions \n" . '> ', false);
      } else {
        $this->stdout($prompt . " $printOptions \n" . "[$default] > ", false);
      }
      $result = fgets($this->stdin);

      if ($result === false) {
        exit;
      }
      $result = trim($result);

      if ($default !== null && ($result === '' || $result === null)) {
        return $default;
      }
      return $result;
    }
  
  
    /**
    * Prompts the user for input, and returns it.
    *
    * @param string $prompt Prompt text.
    * @param mixed $options Array or string of options.
    * @param string $default Default input value.
    * @return Either the default value, or the user-provided input.
    * @access public
    */
    function in($prompt, $options = null, $default = null) {
      if (!$this->interactive) {
        return $default;
      }
      $in = $this->getInput($prompt, $options, $default);

      if ($options && is_string($options)) {
        if (strpos($options, ',')) {
          $options = explode(',', $options);
        } elseif (strpos($options, '/')) {
          $options = explode('/', $options);
        } else {
          $options = array($options);
        }
      }
      if (is_array($options)) {
        while ($in === '' || ($in !== '' && (!in_array(strtolower($in), $options) && !in_array(strtoupper($in), $options)) && !in_array($in, $options))) {
          $in = $this->getInput($prompt, $options, $default);
        }
      }
      return $in;
    }

    /**
    * Outputs a single or multiple messages to stdout. If no parameters
    * are passed outputs just a newline.
    *
    * @param mixed $message A string or a an array of strings to output
    * @param integer $newlines Number of newlines to append
    * @return integer Returns the number of bytes returned from writing to stdout.
    * @access public
    */
    function out($message = null, $newlines = 1) {
      if (is_array($message)) {
        $message = implode($this->nl(), $message);
      }
      return $this->stdout($message . $this->nl($newlines), false);
    }

    /**
    * Outputs a single or multiple error messages to stderr. If no parameters
    * are passed outputs just a newline.
    *
    * @param mixed $message A string or a an array of strings to output
    * @param integer $newlines Number of newlines to append
    * @access public
    */
    function err($message = null, $newlines = 1) {
      if (is_array($message)) {
        $message = implode($this->nl(), $message);
      }
      $this->stderr($message . $this->nl($newlines));
    }
  
    function stderr($string) {
      fwrite($this->stderr, $string);
    }
  
  
    function stdout($string, $newline = true) {

      if ($newline) {
        return fwrite($this->stdout, $string . "\n");
      } else {
        return fwrite($this->stdout, $string);
      }
    }
  
    /**
    * Returns a single or multiple linefeeds sequences.
    *
    * @param integer $multiplier Number of times the linefeed sequence should be repeated
    * @access public
    * @return string
    */
    function nl($multiplier = 1) {
      return str_repeat("\n", $multiplier);
    }

    /**
    * Outputs a series of minus characters to the standard output, acts as a visual separator.
    *
    * @param integer $newlines Number of newlines to pre- and append
    * @access public
    */
    function hr($newlines = 0) {
      $this->out(null, $newlines);
      $this->out('---------------------------------------------------------------');
      $this->out(null, $newlines);
    }
  
  
    public function __construct()
    {
      $this->params['paths'] = './asdf';
      $this->params['output'] = '';
  		$this->stdin = fopen('php://stdin', 'r');
  		$this->stdout = fopen('php://stdout', 'w');
  		$this->stderr = fopen('php://stderr', 'w');
      $this->execute();
      
    }


    /**
    * Execution method always used for tasks
    *
    * @return void
    * @access private
    */

  	function execute() {
  		if (isset($this->params['files']) && !is_array($this->params['files'])) {
  			$this->__files = explode(',', $this->params['files']);
  		}

  		if (isset($this->params['paths'])) {
  			$this->__paths = explode(',', $this->params['paths']);
  		} else {
        
  			$defaultPath = $this->params['working'];
  			$message = sprintf("What is the full path you would like to extract?\nExample: %s\n[Q]uit [D]one", $this->params['root'] . '/myapp');
  			while (true) {
  				$response = $this->in($message, null, $defaultPath);
  				if (strtoupper($response) === 'Q') {
  					$this->out(__('Extract Aborted', true));
  					$this->_stop();
  				} elseif (strtoupper($response) === 'D') {
  					$this->out();
  					break;
  				} elseif (is_dir($response)) {
  					$this->__paths[] = $response;
  					$defaultPath = 'D';
  				} else {
  					$this->err('The directory path you supplied was not found. Please try again.');
  				}
  				$this->out();
  			}
  		}

  		if (isset($this->params['output'])) {
  			$this->__output = $this->params['output'];
  		} else {
  			$message = sprintf("What is the full path you would like to output?\nExample: %s\n[Q]uit", $this->__paths[0] . '/' . 'locale');
  			while (true) {
  				$response = $this->in($message, null, $this->__paths[0] . '/' . 'locale');
  				if (strtoupper($response) === 'Q') {
  					$this->out(__('Extract Aborted', true));
  					$this->_stop();
  				} elseif (is_dir($response)) {
  					$this->__output = $response . '/';
  					break;
  				} else {
  					$this->err('The directory path you supplied was not found. Please try again.');
  				}
  				$this->out();
  			}
  		}

  		if (isset($this->params['merge'])) {
  			$this->__merge = !(strtolower($this->params['merge']) === 'no');
  		} else {
  			$this->out();
  			$response = $this->in(sprintf('Would you like to merge all domains strings into the default.pot file?'), array('y', 'n'), 'n');
  			$this->__merge = strtolower($response) === 'y';
  		}

  		if (empty($this->__files)) {
  			$this->__searchFiles();
  		}
  		$this->__extract();
  	}


    /**
    * Extract text
    *
    * @return void
    * @access private
    */
    function __extract() {
      $this->out();
      $this->out();
      $this->out('Extracting...');
      $this->hr();
      $this->out('Paths:');
      foreach ($this->__paths as $path) {
        $this->out('   ' . realpath($path));
      }
      $this->out('Output Directory: ' . $this->__output);
      $this->hr();
      $this->__extractTokens();
      $this->__buildFiles();
      $this->__writeFiles();
      $this->__paths = $this->__files = $this->__storage = array();
      $this->__strings = $this->__tokens = array();
      $this->out();
      $this->out('Done.');
    }

    /**
    * Extract tokens out of all files to be processed
    *
    * @return void
    * @access private
    */
    function __extractTokens() {
      foreach ($this->__files as $file) {
        $this->__file = $file;
        $this->out(sprintf('Processing %s...', $file));

        $code = file_get_contents($file);
        $allTokens = token_get_all($code);
        $this->__tokens = array();
        $lineNumber = 1;

        foreach ($allTokens as $token) {
          if ((!is_array($token)) || (($token[0] != T_WHITESPACE) && ($token[0] != T_INLINE_HTML))) {
            if (is_array($token)) {
              $token[] = $lineNumber;
            }
            $this->__tokens[] = $token;
          }

          if (is_array($token)) {
            $lineNumber += count(explode("\n", $token[1])) - 1;
          } else {
            $lineNumber += count(explode("\n", $token)) - 1;
          }
        }
        unset($allTokens);
        $this->__parse('translate', array('singular'));
        // $this->__parse('__n', array('singular', 'plural'));
        // $this->__parse('__d', array('domain', 'singular'));
        // $this->__parse('__c', array('singular'));
        // $this->__parse('__dc', array('domain', 'singular'));
        // $this->__parse('__dn', array('domain', 'singular', 'plural'));
        // $this->__parse('__dcn', array('domain', 'singular', 'plural'));
      }
    }

    /**
    * Parse tokens
    *
    * @param string $functionName Function name that indicates translatable string (e.g: '__')
    * @param array $map Array containing what variables it will find (e.g: domain, singular, plural)
    * @return void
    * @access private
    */
    function __parse($functionName, $map) {
      $count = 0;
      $tokenCount = count($this->__tokens);

      while (($tokenCount - $count) > 1) {
        list($countToken, $firstParenthesis) = array($this->__tokens[$count], $this->__tokens[$count + 1]);
        if (!is_array($countToken)) {
          $count++;
          continue;
        }

        list($type, $string, $line) = $countToken;
        if (($type == T_STRING) && ($string == $functionName) && ($firstParenthesis == '(')) {
          $position = $count;
          $depth = 0;

          while ($depth == 0) {
            if ($this->__tokens[$position] == '(') {
              $depth++;
            } elseif ($this->__tokens[$position] == ')') {
              $depth--;
            }
            $position++;
          }

          $mapCount = count($map);
          $strings = $this->__getStrings($position, $mapCount);

          if ($mapCount == count($strings)) {
            extract(array_combine($map, $strings));
            $domain = isset($domain) ? $domain : 'default';
            $string = isset($plural) ? $singular . "\0" . $plural : $singular;
            $this->__strings[$domain][$string][$this->__file][] = $line;
          } else {
            $this->__markerError($this->__file, $line, $functionName, $count);
          }
        }
        $count++;
      }
    }

    /**
    * Get the strings from the position forward
    *
    * @param integer $position Actual position on tokens array
    * @param integer $target Number of strings to extract
    * @return array Strings extracted
    */
    function __getStrings($position, $target) {
      $strings = array();
      while (count($strings) < $target && ($this->__tokens[$position] == ',' || $this->__tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING)) {
        $condition1 = ($this->__tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING && $this->__tokens[$position+1] == '.');
        $condition2 = ($this->__tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING && $this->__tokens[$position+1][0] == T_COMMENT);
        if ($condition1	|| $condition2) {
          $string = '';
          while ($this->__tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING || $this->__tokens[$position][0] == T_COMMENT || $this->__tokens[$position] == '.') {
            if ($this->__tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING) {
              $string .= $this->__formatString($this->__tokens[$position][1]);
            }
            $position++;
          }
          if ($this->__tokens[$position][0] == T_COMMENT || $this->__tokens[$position] == ',' || $this->__tokens[$position] == ')') {
            $strings[] = $string;
          }
        } else if ($this->__tokens[$position][0] == T_CONSTANT_ENCAPSED_STRING) {
          $strings[] = $this->__formatString($this->__tokens[$position][1]);
        }
        $position++;
      }
      return $strings;
    }
	
    /**
    * Build the translate template file contents out of obtained strings
    *
    * @return void
    * @access private
    */
    function __buildFiles() {
      foreach ($this->__strings as $domain => $strings) {
        foreach ($strings as $string => $files) {
          $occurrences = array();
          foreach ($files as $file => $lines) {
            $occurrences[] = $file . ':' . implode(';', $lines);
          }
          $occurrences = implode("\n; ", $occurrences);

          $header = '; ' . str_replace($this->__paths, '', $occurrences) . "\n";

          $sentence = "\"{$string}\";";
          $sentence .= "\"\"\n\n";

          $this->__store($domain, $header, $sentence);
          if ($domain != 'default' && $this->__merge) {
            $this->__store('default', $header, $sentence);
          }
        }
      }
    }

    /**
    * Prepare a file to be stored
    *
    * @return void
    * @access private
    */
    function __store($domain, $header, $sentence) {
      if (!isset($this->__storage[$domain])) {
        $this->__storage[$domain] = array();
      }
      if (!isset($this->__storage[$domain][$sentence])) {
        $this->__storage[$domain][$sentence] = $header;
      } else {
        $this->__storage[$domain][$sentence] .= $header;
      }
    }

    /**
    * Write the files that need to be stored
    *
    * @return void
    * @access private
    */
    function __writeFiles() {
      $overwriteAll = true;
      
      foreach ($this->__storage as $domain => $sentences) {
        $output = $this->__writeHeader();
        foreach ($sentences as $sentence => $header) {
          $output .= $header . $sentence;
        }

        $filename = $domain . '.ini';
        $File = new File($this->__output . $filename);
        $response = '';
        while ($overwriteAll === false && $File->exists() && strtoupper($response) !== 'Y') {
          $this->out();
          $response = $this->in(sprintf('Error: %s already exists in this location. Overwrite? [Y]es, [N]o, [A]ll', $filename), array('y', 'n', 'a'), 'y');
          if (strtoupper($response) === 'N') {
            $response = '';
            while ($response == '') {
              $response = $this->in(sprintf("What would you like to name this file?\nExample: %s", 'new_' . $filename), null, 'new_' . $filename);
              $File = new File($this->__output . $response);
              $filename = $response;
            }
          } elseif (strtoupper($response) === 'A') {
            $overwriteAll = true;
          }
        }
        $File->write($output);
        $File->close();
      }
    }

    /**
    * Build the translation template header
    *
    * @return string Translation template header
    * @access private
    */
    function __writeHeader() {
      $output  = "; LANGUAGE translation of Application\n";
      $output .= "; Copyright YEAR NAME <EMAIL@ADDRESS>\n";
      $output .= ";\n";
      $output .= ";\"Creation-Date: " . date("Y-m-d H:iO") . "\\n\"\n";
      $output .= ";\"Last-Translator: NAME <EMAIL@ADDRESS>\\n\"\n";
      $output .= "\n";
      return $output;
    }

    /**
    * Format a string to be added as a translateable string
    *
    * @param string $string String to format
    * @return string Formatted string
    * @access private
    */
    function __formatString($string) {
      $quote = substr($string, 0, 1);
      $string = substr($string, 1, -1);
      if ($quote == '"') {
        $string = stripcslashes($string);
      } else {
        $string = strtr($string, array("\\'" => "'", "\\\\" => "\\"));
      }
      $string = str_replace("\r\n", "\n", $string);
      return addcslashes($string, "\0..\37\\\"");
    }

    /**
    * Indicate an invalid marker on a processed file
    *
    * @param string $file File where invalid marker resides
    * @param integer $line Line number
    * @param string $marker Marker found
    * @param integer $count Count
    * @return void
    * @access private
    */
    function __markerError($file, $line, $marker, $count) {
      $this->out(sprintf("Invalid marker content in %s:%s\n* %s(", $file, $line, $marker), true);
      $count += 2;
      $tokenCount = count($this->__tokens);
      $parenthesis = 1;

      while ((($tokenCount - $count) > 0) && $parenthesis) {
        if (is_array($this->__tokens[$count])) {
          $this->out($this->__tokens[$count][1], false);
        } else {
          $this->out($this->__tokens[$count], false);
          if ($this->__tokens[$count] == '(') {
            $parenthesis++;
          }

          if ($this->__tokens[$count] == ')') {
            $parenthesis--;
          }
        }
        $count++;
      }
      $this->out("\n", true);
    }

    /**
    * Search files that may contain translateable strings
    *
    * @return void
    * @access private
    */
    function __searchFiles() {
      foreach ($this->__paths as $path) {
        
        $Folder = new Folder($path);
        $files = $Folder->findRecursive('.*\.(php|ctp|thtml|inc|tpl)', true);
        $this->__files = array_merge($this->__files, $files);
      }

    }
  }

  new ExtractTask;
