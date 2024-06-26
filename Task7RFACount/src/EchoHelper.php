<?php

class EchoHelper {
	protected $h;

	public function __construct( Helper $h ) {
		$this->h = $h;
	}

	/** Echo to the browser instantly, without a delay. Also, convert to HTML so we don't need to use Content-Type:text/plain, which displays HTML error messages incorrectly. Also, put a border around each message. */
	public function echoAndFlush( string $str, string $type ): void {
		global $SHORT_WIKICODE_IN_CONSOLE, $CHARACTERS_TO_ECHO, $SHOW_API_READS;

		if ( $type == 'api_read' && !$SHOW_API_READS ) {
			return;
		}

		switch ( $type ) {
			case 'api_read':
				$color = 'lightgray';
				$description = 'API read';
				$str = htmlentities( $str );
				$str = nl2br( $str );
				$str = $this->h->nbsp( $str );
				break;
			case 'api_write':
				$color = '#FFCC66';
				$description = 'API write';
				break;
			case 'variable':
				$color = 'lawngreen';
				$description = 'Variable';
				$str = htmlentities( $str );
				$str = nl2br( $str );
				$str = $this->h->nbsp( $str );
				break;
			case 'error':
				$color = 'salmon';
				$description = 'Error';
				$str = htmlentities( $str );
				$str = nl2br( $str );
				$str = $this->h->nbsp( $str );
				break;
			case 'newtopic':
				$color = 'yellow';
				$description = 'Starting new topic';
				$str = htmlentities( $str );
				$str = nl2br( $str );
				$str = $this->h->nbsp( $str );
				break;
			case 'message':
				$color = 'lightblue';
				$description = "Message";
				break;
			case 'complete':
				$color = 'yellow';
				$description = 'Run complete';
				$str = htmlentities( $str );
				$str = nl2br( $str );
				$str = $this->h->nbsp( $str );
				break;
		}

		if ( $SHORT_WIKICODE_IN_CONSOLE ) {
			$str2 = substr( $str, 0, $CHARACTERS_TO_ECHO );
			if ( $str2 != $str ) {
				$str2 .= "\n\n[...]";
			}
			$str = $str2;
		}

		$str = '<div style="border: 2px solid black; margin-bottom: 1em; background-color: ' . $color . ';"><b><u>' . $description . '</u></b>:<br />' . $str . '</div>';

		echo $str;
		flush();
		if ( ob_get_level() > 0 ) {
			ob_flush();
		}
	}

	public function html_var_export( $arr, $type ) {
		return $this->echoAndFlush( var_export( $arr, true ), $type );
	}

	/** Don't forget to use continue; after this is called, to continue execution of the outer loop */
	public function logError( $error_message ) {
		$this->echoAndFlush( 'ERROR, SKIPPING THE REST OF THIS TOPIC: ' . $error_message, 'error' );

		// TODO: replace {{User:NovemBot/Promote}} with "Unable to promote due to $error_message. ~~~~"
	}
}
