<?php
/**
 * Created by PhpStorm.
 * User: anuj
 * Date: 20/8/18
 * Time: 12:53 PM
 */

namespace TBETool;


use Aws\Polly\PollyClient;
use Exception;

/**
 * Class AwsPolly
 * @property  PollyClient $Client
 */
class AwsPolly
{

    private $AWS_Key;
    private $AWS_Secret;
    private $AWS_Region;
    private $AWS_Version = 'latest';
    private $AWS_http_verify = false;
    private $Client;

    private $used_voice = 'Ivy';
    private $used_language = 'en-US';
    private $used_extension = 'mp3';
    private $output_path;

    private $file_extensions = [
        "json",
        "mp3",
        "ogg_vorbis",
        "pcm"
    ];

    private $language_voice = [
        'da-DK' => [
            'Mads',
            'Naja'
        ],
        'nl-NL' => [
            'Ruben',
            'Lotte'
        ],
        'en-AU' => [
            'Nicole',
            'Russell'
        ],
        'en-GB' => [
            'Brian',
            'Emma',
            'Amy'
        ],
        'en-IN' => [
            'Aditi',
            'Raveena'
        ],
        'en-US' => [
            'Joey',
            'Justin',
            'Matthew',
            'Ivy',
            'Joanna',
            'Kendra',
            'Kimberly',
            'Salli'
        ],
        'en-GB-WLS' => [
            'Geraint'
        ],
        'fr-FR' => [
            'Mathieu',
            'Celine',
            'Lea'
        ],
        'fr-CA' => [
            'Chantal'
        ],
        'de-DE' => [
            'Hans',
            'Marlene',
            'Vicki'
        ],
        'hi-IN' => [
            'Aditi'
        ],
        'is-IS' => [
            'Karl',
            'Dora'
        ],
        'it-IT' => [
            'Giorgio',
            'Carla'
        ],
        'ja-JP' => [
            'Takumi',
            'Mizuki',
            'Seoyeon'
        ],
        'ko-KR' => [
            'Seoyeon'
        ],
        'nb-NO' => [
            'Liv'
        ],
        'pl-PL' => [
            'Jacek',
            'Jan',
            'Ewa',
            'Maja'
        ],
        'pt-BR' => [
            'Ricardo',
            'Camila',
            'Vitoria'
        ],
        'pt-PT' => [
            'Cristiano',
            'Ines'
        ],
        'ro-RO' => [
            'Carmen'
        ],
        'ru-RU' => [
            'Maxim',
            'Tatyana'
        ],
        'es-ES' => [
            'Enrique',
            'Conchita'
        ],
        'es-US' => [
            'Miguel',
            'Penelope'
        ],
        'sv-SE' => [
            'Astrid',
        ],
        'tr-TR' => [
            'Filiz'
        ],
        'cy-GB' => [
            'Gwyneth'
        ],
        'arb' => [
            'Zeina'
        ],
        'cmn-CN' => [
            'Zhiyu'
        ]
    ];

    /**
     * AwsPolly constructor.
     * @param $aws_key
     * @param $aws_secret
     * @param null $aws_region
     * @param null $aws_version
     * @param bool $aws_http_verify
     * @throws Exception
     */
    function __construct($aws_key, $aws_secret, $aws_region, $aws_version = null, $aws_http_verify = false)
    {
        /**
         * Set AWS Key
         */
        if (!empty($aws_key) || $aws_key !== null) {
            $this->AWS_Key = $aws_key;
        } else {
            throw new Exception('Key is required');
        }

        /**
         * Set AWS Secret
         */
        if (!empty($aws_secret) || $aws_secret !== null) {
            $this->AWS_Secret = $aws_secret;
        } else {
            throw new Exception('Secret is missing');
        }

        // set s3 region
        if (!empty($aws_region) || $aws_region !== null) {
            $this->AWS_Region = $aws_region;
        } else {
            throw new Exception('Region is missing');
        }

        // set s3 version
        if (!empty($aws_version) || $aws_version !== null) {
            $this->AWS_Version = $aws_version;
        }
        // set s3 http verify
        $this->AWS_http_verify = $aws_http_verify;

        /**
         * Initialize AWS Client with the provided credentials
         */
        $this->setAwsClient();
    }

    /**
     * Get supported voices
     */
    public function getVoices()
    {
        $voices = [];

        foreach ($this->language_voice as $language) {
            foreach ($language as $voice) {
                $voices[] = $voice;
            }
        }

        return $voices;
    }

    /**
     * Get supported languages
     */
    public function getLanguages()
    {
        $languages = [];

        foreach ($this->language_voice as $language => $value) {
            $languages[] = $language;
        }

        return $languages;
    }


    /**
     * *****************************************
     *  S 3  C L I E N T  O B J E C T
     * *****************************************
     */

    private function setAwsClient()
    {
        $client = new PollyClient([
            'version' => $this->AWS_Version,
            'region' => $this->AWS_Region,
            'http' => [
                'verify' => $this->AWS_http_verify
            ],
            'credentials' => [
                'key' => $this->AWS_Key,
                'secret' => $this->AWS_Secret
            ]
        ]);

        $this->Client = $client;
    }

    /**
     * ***********************************************
     *    P O L L Y  M E T H O D S
     * ***********************************************
     */


    /**
     * @param $text
     * @param array $param
     * @return string
     * @throws Exception
     */
    public function textToVoice($text, $param = [])
    {
        if (empty($text))
            throw new Exception('Text is empty');

        if (!empty($param['voice']))
            $this->used_voice = $param['voice'];

        if (!empty($param['language']))
            $this->used_language = $param['language'];

        if (!empty($param['output_format']))
            $this->used_extension = $param['output_format'];

        if (!empty($param['output_path']))
            $this->output_path = $param['output_path'];


        /************************
         *  Processing
         ************************/
        if (empty($this->output_path))
            throw new Exception('Output path not specified. Either set output path with setOutputPath() function or pass second parameter to this function with absolute path. eg., [\'output_path\' => \'path_to_save\']');

        if (empty($this->used_voice))
            throw new Exception('Voice is not set. Set voice by passing [\'voice\' => \'\'] in second parameter');

        if (empty($this->used_language))
            throw new Exception('Language is not set. Set language by passing [\'language\' => \'\'] in second parameter');

        if (!in_array($this->used_language, $this->getLanguages()))
            throw new Exception($this->used_language . ' language is not supported. use getLanguages() to see all supported languages');
        
        if (!in_array($this->used_extension, $this->file_extensions))
            throw new Exception($this->used_extension . ' extension is not supported');

        if (!in_array($this->used_voice, $this->getVoices()))
            throw new Exception($this->used_voice . ' voice is not supported. use getVoices() to see all supported voices');

        if (!in_array($this->used_voice, $this->language_voice[$this->used_language]))
            throw new Exception($this->used_voice . ' is not supported in language ' . $this->used_language . '. Supported voices are ' . implode(',', $this->language_voice[$this->used_language]));

        /**
         * Get file name
         */
        $file_name = $this->_getFileName();

        $voice = $this->Client->synthesizeSpeech([
            'LanguageCode' => $this->used_language,
            'OutputFormat' => $this->used_extension,
            'Text' => $text,
            'TextType' => 'text',
            'VoiceId' => $this->used_voice
        ]);

        $voiceContent = $voice->get('AudioStream')->getContents();

        file_put_contents($file_name, $voiceContent);

        if (is_file($file_name))
            return $file_name;

        throw new Exception('File could not be created');
    }

    /**
     * Generate file name, create directory and return absolute file path
     * @return string
     */
    private function _getFileName()
    {
        $file_name = time() . '-' . str_shuffle(time()) . '.' . $this->used_extension;

        $this->output_path = rtrim($this->output_path, '/');

        if (!is_dir($this->output_path)) {
            mkdir($this->output_path, 0777, true);
        }

        $absolute_file_path = $this->output_path . '/' . $file_name;

        return $absolute_file_path;
    }
}
