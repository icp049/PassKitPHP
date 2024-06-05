<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use Firebase\JWT\JWT;

class WalletPass {
    private $credentials;
    private $client;
    private $baseUrl = 'https://walletobjects.googleapis.com/walletobjects/v1';
    private $issuerId = '';
    private $classId;

    public function __construct($credentialsPath) {
        $this->credentials = json_decode(file_get_contents($credentialsPath), true);
        $this->client = new Client();
        $this->classId = $this->issuerId . '.codelab_class';
    }

    private function getAuthToken() {
        $tokenUri = 'https://oauth2.googleapis.com/token';

        $jwt = JWT::encode([
            'iss' => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/wallet_object.issuer',
            'aud' => $tokenUri,
            'exp' => time() + 3600,
            'iat' => time()
        ], $this->credentials['private_key'], 'RS256');

        $response = $this->client->post($tokenUri, [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['access_token'];
    }

    public function createPassClass() {
        $genericClass = [
            'id' => $this->classId,
            'classTemplateInfo' => [
                'cardTemplateOverride' => [
                    'cardRowTemplateInfos' => [
                        [
                            'twoItems' => [
                                'startItem' => [
                                    'firstValue' => [
                                        'fields' => [
                                            ['fieldPath' => 'object.textModulesData["points"]']
                                        ]
                                    ]
                                ],
                                'endItem' => [
                                    'firstValue' => [
                                        'fields' => [
                                            ['fieldPath' => 'object.textModulesData["contacts"]']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'detailsTemplateOverride' => [
                    'detailsItemInfos' => [
                        [
                            'item' => [
                                'firstValue' => [
                                    'fields' => [
                                        ['fieldPath' => 'class.imageModulesData["event_banner"]']
                                    ]
                                ]
                            ]
                        ],
                        [
                            'item' => [
                                'firstValue' => [
                                    'fields' => [
                                        ['fieldPath' => 'class.textModulesData["game_overview"]']
                                    ]
                                ]
                            ]
                        ],
                        [
                            'item' => [
                                'firstValue' => [
                                    'fields' => [
                                        ['fieldPath' => 'class.linksModuleData.uris["official_site"]']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'imageModulesData' => [
                [
                    'mainImage' => [
                        'sourceUri' => [
                            'uri' => 'https://storage.googleapis.com/wallet-lab-tools-codelab-artifacts-public/google-io-2021-card.png'
                        ],
                        'contentDescription' => [
                            'defaultValue' => [
                                'language' => 'en-US',
                                'value' => 'Google I/O 2022 Banner'
                            ]
                        ]
                    ],
                    'id' => 'event_banner'
                ]
            ],
            'textModulesData' => [
                [
                    'header' => 'Gather points meeting new people at Google I/O',
                    'body' => 'Join the game and accumulate points in this badge by meeting other attendees in the event.',
                    'id' => 'game_overview'
                ]
            ],
            'linksModuleData' => [
                'uris' => [
                    [
                        'uri' => 'https://io.google/2022/',
                        'description' => 'Official I/O \'22 Site',
                        'id' => 'official_site'
                    ]
                ]
            ]
        ];

        $authToken = $this->getAuthToken();

        try {
            $response = $this->client->get("{$this->baseUrl}/genericClass/{$this->classId}", [
                'headers' => ['Authorization' => "Bearer {$authToken}"]
            ]);
            if ($response->getStatusCode() == 200) {
                echo "Class already exists<br>";
                echo $response->getBody();
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() == 404) {
                // Class does not exist, create it
                $response = $this->client->post("{$this->baseUrl}/genericClass", [
                    'headers' => ['Authorization' => "Bearer {$authToken}"],
                    'json' => $genericClass
                ]);

                echo "Class insert response<br>";
                echo $response->getBody();
            } else {
                echo $e->getMessage();
            }
        }
    }

    public function createPassObject($email) {
        $objectSuffix = preg_replace('/[^\w.-]/', '_', $email);
        $objectId = "{$this->issuerId}.{$objectSuffix}";

        $genericObject = [
            'id' => $objectId,
            'classId' => $this->classId,
            'genericType' => 'GENERIC_TYPE_UNSPECIFIED',
            'hexBackgroundColor' => '#4285f4',
            'logo' => [
                'sourceUri' => [
                    'uri' => 'https://storage.googleapis.com/wallet-lab-tools-codelab-artifacts-public/pass_google_logo.jpg'
                ]
            ],
            'cardTitle' => [
                'defaultValue' => [
                    'language' => 'en',
                    'value' => 'Google I/O \'22'
                ]
            ],
            'subheader' => [
                'defaultValue' => [
                    'language' => 'en',
                    'value' => 'Attendee'
                ]
            ],
            'header' => [
                'defaultValue' => [
                    'language' => 'en',
                    'value' => 'test Test'
                ]
            ],
            'barcode' => [
                'type' => 'QR_CODE',
                'value' => $objectId
            ],
            'heroImage' => [
                'sourceUri' => [
                    'uri' => 'https://storage.googleapis.com/wallet-lab-tools-codelab-artifacts-public/google-io-hero-demo-only.jpg'
                ]
            ],
            'textModulesData' => [
                [
                    'header' => 'POINTS',
                    'body' => '1234',
                    'id' => 'points'
                ],
                [
                    'header' => 'CONTACTS',
                    'body' => '20',
                    'id' => 'contacts'
                ]
            ]
        ];

        // Create the signed JWT and link
        $claims = [
            'iss' => $this->credentials['client_email'],
            'aud' => 'google',
            'origins' => [],
            'typ' => 'savetowallet',
            'payload' => [
                'genericObjects' => [$genericObject]
            ]
        ];

        $token = JWT::encode($claims, $this->credentials['private_key'], 'RS256');
        $saveUrl = "https://pay.google.com/gp/v/save/{$token}";

        return $saveUrl;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $walletPass = new WalletPass('config/walletconfig.json');
    $walletPass->createPassClass();
    $saveUrl = $walletPass->createPassObject($email);

    echo "<a href='{$saveUrl}'><img src='wallet-button.png'></a>";
}
