<?php
	/**
	 * The MIT License (MIT)
	 * 
	 * Copyright (c) 2013 Gerard (Gerry) Caulfield
	 * 
	 * Permission is hereby granted, free of charge, to any person obtaining a copy
	 * of this software and associated documentation files (the "Software"), to deal
	 * in the Software without restriction, including without limitation the rights
	 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	 * copies of the Software, and to permit persons to whom the Software is
	 * furnished to do so, subject to the following conditions:
	 * 
	 * The above copyright notice and this permission notice shall be included in
	 * all copies or substantial portions of the Software.
	 * 
	 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	 * THE SOFTWARE.
	 */

	/**
	 * Add "CurlLog::" in front of any calls to curl_setopt() (or just the ones that set CURLOPT_POSTFIELDS and
	 * CURLOPT_INFILE, if that's easier) and curl_exec()
	 *
	 * If code needs to do header processing via CURLOPT_HEADERFUNCTION, then you should pass it to us as a
	 * callback and make sure it's public so we can call it.
	 * e.g. CurlLog::$headerCallback = array($this, 'curlHeader');
	 */
	class CurlLog{
		protected static $request;
		protected static $upload;
		public static $headerCallback;

		public static function curl_exec(&$ch){
	        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array('self', 'dbg_curl_data')); // handle header lines received in the response
	        curl_setopt($ch, CURLOPT_WRITEFUNCTION, array('self', 'dbg_curl_data')); // handle data received in the response
	        // curl_setopt($ch, CURLINFO_HEADER_OUT, true);

	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	        // curl_setopt($ch, CURLOPT_HEADER, 0);
	        curl_setopt($ch, CURLOPT_VERBOSE, true);

	        $curlResponse = curl_exec($ch);

	        $info = curl_getinfo($ch);
	        $requestHeaders = $info['request_header'];
	        $requestUrl = $info['url'];

	        $requestData = array();

	        if(isset(self::$request)){
	        	$request = self::$request;
	        	$requestData[] = 'request';
	        }

	        if(isset(self::$upload)){
	        	$upload = self::$upload;
	        	$requestData[] = 'upload';
	        }

	        $responseHeaders = self::dbg_curl_data(null);
	        $response = $curlResponse;

	        $debug = compact('requestUrl', 'requestHeaders', $requestData, 'responseHeaders', 'response');
	        d($debug);

	        return $curlResponse;
	    }

	    /**
	     * @todo Add support for passing an array of options
	     */
	    public static function curl_setopt(&$ch, $option, &$data){

	    	switch($option){
	    		case CURLOPT_POSTFIELDS:
	    			self::$postData = $data;
	    			break;
	    		case CURLOPT_INFILE:
			    	$startingPosition = ftell($data);
	    			self::$upload = urldecode(stream_get_contents($data));
	    			// Rewind back to the postion of the handler before we called stream_get_contents()
	    			fseek($data, $startingPosition);
	    			break;
	    	}
	    	curl_setopt($ch, $option, $data);
	    }

	    /**
	     * @param $curl
	     * @param null $data
	     * @return int
	     */
	    protected static function dbg_curl_data($curl, $data=null){
	        static $buffer = '';

	        if(is_null($curl)){
	            $r = $buffer;
	            $buffer = '';
	        }
	        else{
	            $buffer .= $data;
	            $r = strlen($data);
	        }

	        if(self::$headerCallback){
		        if(!is_callable(self::$headerCallback)){
		        	trigger_error('Your callback is not callable', E_USER_ERROR);
		        }
	        	call_user_func(self::$headerCallback, $curl, $data);
	        }

            return $r;
	    }
	}