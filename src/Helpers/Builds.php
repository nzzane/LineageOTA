<?php
    /*
        The MIT License (MIT)

        Copyright (c) 2014 Julian Xhokaxhiu

        Permission is hereby granted, free of charge, to any person obtaining a copy of
        this software and associated documentation files (the "Software"), to deal in
        the Software without restriction, including without limitation the rights to
        use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
        the Software, and to permit persons to whom the Software is furnished to do so,
        subject to the following conditions:

        The above copyright notice and this permission notice shall be included in all
        copies or substantial portions of the Software.

        THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
        IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
        FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
        COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
        IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
        CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
    */

    namespace JX\CmOta\Helpers;

    use \Flight;
    use Build;

    class Builds {

        // This will contain the build list based on the current request
    	private $builds = array();

        private $postData = array();

        /**
         * Constructor of the Builds class.
         */
    	public function __construct() {
            // Set required paths for properly builds Urls later
            Flight::cfg()->set( 'buildsPath', Flight::cfg()->get('basePath') . '/builds/full' );
            Flight::cfg()->set( 'deltasPath', Flight::cfg()->get('basePath') . '/builds/deltas' );

            // Get the current POST request data
            $this->postData = json_decode( Flight::request()->body, true);

            // Internal Initialization routines
    		$this->getBuilds();
    	}

        /**
         * Return a valid response list of builds available based on the current request
         * @return array An array preformatted with builds
         */
    	public function get() {
    		$ret = array();

            foreach ( $this->builds as $build ) {
                array_push( $ret, array(
                    'incremental' => $build->getIncremental(),
                    'api_level' => $build->getApiLevel(),
                    'url' => $build->getUrl(),
                    'timestamp' => $build->getTimestamp(),
                    'md5sum' => $build->getMD5(),
                    'changes' => $build->getChangelogUrl(),
                    'channel' => $build->getChannel(),
                    'filename' => $build->getFilename()
                ));
            }

            return $ret;
    	}

        /**
         * Return a valid response of the delta build (if available) based on the current request
         * @return array An array preformatted with the delta build
         */
    	public function getDelta() {
            $ret = false;

            $source = $this->postData['source_incremental'];
            $target = $this->postData['target_incremental'];
            if ( $source != $target ) {
                $sourceToken = null;
                foreach ($this->builds as $build) {
                    if ( $build->getIncremental() == $target ) {
                        $delta = $sourceToken->getDelta($build);
                        $ret = array(
                            'date_created_unix' => $delta['timestamp'],
                            'filename' => $delta['filename'],
                            'download_url' => $delta['url'],
                            'api_level' => $delta['api_level'],
                            'md5sum' => $delta['md5'],
                            'incremental' => $delta['incremental']
                        );
                    } else if ( $build->getIncremental() == $source ) {
                        $sourceToken = $build;
                    }
                }
            }

    		return $ret;
    	}

        /* Utility / Internal */

    	private function getBuilds() {
            // Get physical paths of where the files resides
            $path = Flight::cfg()->get('realBasePath') . '/builds/full';
            // Get the file list and parse it
    		$files = preg_grep( '/^([^.Thumbs])/', scandir( $path ) );
            if ( count( $files ) > 0  ) {
                foreach ( $files as $file ) {
                    $build = new Build( $file, $path);

                    if ( $build->isValid( $this->postData['params'] ) ) {
                        array_push( $this->builds , $build );
                    }
                }
            }
    	}

    }