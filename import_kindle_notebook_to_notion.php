<?php
/**
 * 
 *  import_kindle_notebook_to_notion.php
 * 
 *  Author:
 *      GitHub: @happy-se-life
 * 
 *  Usage:
 *      $ php import_kindle_notebook_to_notion.php notebook.html
 *
 *  This software is released under the MIT License, see LICENSE.
 * 
 */
 require('config.php');

/**
 *  Return array of an block item.
 */
function getArrayBlock($type, $content) {
    return [
        "object" => "block",
        "type" => "$type",
        "$type" => [
            "text" => [
                [
                    "type" => "text",
                    "text" => [ "content" => "$content" ]
                ]
            ]
        ]
    ];
}

/**
 *  Create post data.
 */
function createPostData($html) {
    if (!is_file($html)) {
        echo "Please specify the correct file.\n";
        exit(1);
    }

    // Read html
    $doc = new DOMDocument();
    $doc->loadHTMLFile($html);
    $elements = $doc->getElementsByTagName('div');

    $children = [];
    $bookTitle = [];
    $authors = [];

    // Create children array contained in post_data
    foreach ($elements as $elm) {
        $text = trim($elm->nodeValue);
        switch ($elm->getAttribute('class')) {
            case "bookTitle" :
                $bookTitle = $text;
                break;
            case "authors" :
                $authors = $text;
                break;
            case "citation" :
                if (strlen($text) != 0) {
                    $children[] = getArrayBlock("paragraph", $text);
                }
                break;
            case "sectionHeading" :
                $children[] = getArrayBlock("heading_2", $text);
                break;
            case "noteHeading" :
                $children[] = getArrayBlock("heading_3", $text);
                break;
            case "noteText" :
                $children[] = getArrayBlock("paragraph", $text);
                break;
            default :
                break;            
        }
    }

    // Create post data
    $post_data = [
        "parent" => [ "database_id" => MY_NOTION_DATABASE_ID ],
        "properties" => [
            "Name" => [
                "title" => [
                    [
                        "text" => [
                            "content" => "$bookTitle"
                        ]
                    ]
                ]
            ],
            "Authors" => [
                "rich_text" => [
                    [
                        "text" => [
                            "content" => "$authors"
                        ]
                    ]
                ]
            ]
        ],
        "children" => $children,
    ];

    return $post_data;
}

/**
 *  Import to notion.
 */
function import($html) {

    $post_data = createPostData($html);

    $header = [
        "Authorization: Bearer " . MY_NOTION_TOKEN,
        "Content-Type: application/json",
        "Notion-Version: " . NOTION_API_VERSION,
    ];

    $curl = curl_init( NOTION_API_ENDPOINT );

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_data));

    // Post
    $result = curl_exec($curl);

    if ($result) {
        echo "The import process was successful.\n";
    } else {
        echo "Import process failed.\n";
    }

    return;
}

import($argv[1]);
