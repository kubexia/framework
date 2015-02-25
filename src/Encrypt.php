<?php
namespace Kubexia;

class Encrypt {
        
        protected static $enckey = 'Kub3x14';
    
        public $config = array();
        
	/**
	 * @var  string  default instance name
	 */
	public static $default = 'default';

	/**
	 * @var  array  Encrypt class instances
	 */
	public static $instances = array();

	/**
	 * @var  string  OS-dependent RAND type to use
	 */
	protected static $_rand;

	/**
	 * Returns a singleton instance of Encrypt. An encryption key must be
	 * provided in your "encrypt" configuration file.
	 *
	 *     $encrypt = Encrypt::instance();
	 *
	 * @param   string  $name   configuration group name
	 * @return  Encrypt
	 */
	public static function getInstance($config=array(),$name = NULL)
	{
		if ($name === NULL)
		{
			// Use the default instance name
			$name = Encrypt::$default;
		}

		if ( ! isset(Encrypt::$instances[$name]))
		{

			if ( ! isset($config['key']))
			{
				//die('No encryption key is defined in the encryption configuration');
                                $config['key'] = Encrypt::$enckey;
			}

			if ( ! isset($config['mode']))
			{
				// Add the default mode
				$config['mode'] = MCRYPT_MODE_CBC;
			}

			if ( ! isset($config['cipher']))
			{
				// Add the default cipher
				$config['cipher'] = MCRYPT_RIJNDAEL_128;
			}

			// Create a new instance
			Encrypt::$instances[$name] = new Encrypt($config['key'], $config['mode'], $config['cipher']);
		}

		return Encrypt::$instances[$name];
	}

	/**
	 * Creates a new mcrypt wrapper.
	 *
	 * @param   string  $key    encryption key
	 * @param   string  $mode   mcrypt mode
	 * @param   string  $cipher mcrypt cipher
	 */
	public function __construct($key, $mode, $cipher)
	{
		// Find the max length of the key, based on cipher and mode
		$size = mcrypt_get_key_size($cipher, $mode);

		if (isset($key[$size]))
		{
			// Shorten the key to the maximum size
			$key = substr($key, 0, $size);
		}

		// Store the key, mode, and cipher
		$this->_key    = $key;
		$this->_mode   = $mode;
		$this->_cipher = $cipher;

		// Store the IV size
		$this->_iv_size = mcrypt_get_iv_size($this->_cipher, $this->_mode);
	}

	/**
	 * Encrypts a string and returns an encrypted string that can be decoded.
	 *
	 *     $data = $encrypt->encode($data);
	 *
	 * The encrypted binary data is encoded using [base64](http://php.net/base64_encode)
	 * to convert it to a string. This string can be stored in a database,
	 * displayed, and passed using most other means without corruption.
	 *
	 * @param   string  $data   data to be encrypted
	 * @return  string
	 */
	public function encode($data)
	{
		// Set the rand type if it has not already been set
		if (Encrypt::$_rand === NULL)
		{
                    $isWin = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? TRUE : FALSE;
			if ($isWin)
			{
				// Windows only supports the system random number generator
				Encrypt::$_rand = MCRYPT_RAND;
			}
			else
			{
				if (defined('MCRYPT_DEV_URANDOM'))
				{
					// Use /dev/urandom
					Encrypt::$_rand = MCRYPT_DEV_URANDOM;
				}
				elseif (defined('MCRYPT_DEV_RANDOM'))
				{
					// Use /dev/random
					Encrypt::$_rand = MCRYPT_DEV_RANDOM;
				}
				else
				{
					// Use the system random number generator
					Encrypt::$_rand = MCRYPT_RAND;
				}
			}
		}

		if (Encrypt::$_rand === MCRYPT_RAND)
		{
			// The system random number generator must always be seeded each
			// time it is used, or it will not produce true random results
			mt_srand();
		}

		// Create a random initialization vector of the proper size for the current cipher
		$iv = mcrypt_create_iv($this->_iv_size, Encrypt::$_rand);

		// Encrypt the data using the configured options and generated iv
		$data = mcrypt_encrypt($this->_cipher, $this->_key, $data, $this->_mode, $iv);

		// Use base64 encoding to convert to a string
		return base64_encode($iv.$data);
	}

	/**
	 * Decrypts an encoded string back to its original value.
	 *
	 *     $data = $encrypt->decode($data);
	 *
	 * @param   string  $data   encoded string to be decrypted
	 * @return  FALSE   if decryption fails
	 * @return  string
	 */
	public function decode($data)
	{
		// Convert the data back to binary
		$data = base64_decode($data, TRUE);

		if ( ! $data)
		{
			// Invalid base64 data
			return FALSE;
		}

		// Extract the initialization vector from the data
		$iv = substr($data, 0, $this->_iv_size);

		if ($this->_iv_size !== strlen($iv))
		{
			// The iv is not the expected size
			return FALSE;
		}

		// Remove the iv from the data
		$data = substr($data, $this->_iv_size);

		// Return the decrypted data, trimming the \0 padding bytes from the end of the data
		return trim(mcrypt_decrypt($this->_cipher, $this->_key, $data, $this->_mode, $iv));
	}

}
