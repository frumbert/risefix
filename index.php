<?php

function delTree($dir) {
    $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            if (is_dir("$dir/$file")) {
                delTree("$dir/$file");
            } else {
                unlink("$dir/$file");
            }
        }
    return rmdir($dir);
}

function unzip($file, $destination) {
    if (extension_loaded('zip')) {
        $zip = new ZipArchive;
        $res = $zip->open($file);
        if ($res === TRUE) {
            $zip->extractTo($destination);
            $zip->close();
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

// zip a folder
function zip($source, $destination) {
    if (extension_loaded('zip')) {
        if (file_exists($source)) {
            $zip = new ZipArchive();
            if ($zip->open($destination, ZIPARCHIVE::CREATE)) {
                $source = realpath($source);
                if (is_dir($source)) {
                    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
                    foreach ($files as $file) {
                        $file = realpath($file);
                        if (is_dir($file)) {
                            $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                        } else if (is_file($file)) {
                            $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                        }
                    }
                } else if (is_file($source)) {
                    $zip->addFromString(basename($source), file_get_contents($source));
                }
            }
            return $zip->close();
        }
    } else {
        return false;
    }
}

$results = '';

// get uploaded file
if (isset($_FILES['file'])) {
    $url = trim($_POST["url"]);
    $css = trim($_POST["customcss"]);
    $matchurl = preg_replace("/([^A-Za-z0-9])/", "\\\\$1", $url); // addslashes for every character except a-zA-Z0-9

    $file = $_FILES['file'];

    $file_name = $file['name'];
    $file_tmp_name = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    $file_type = $file['type'];

    $dirname = basename($file_name, ".zip");
    if (file_exists($dirname)) {
        delTree($dirname);
    }
    mkdir($dirname);
    if (unzip($file_tmp_name, $dirname)) {

        $index = file_get_contents($dirname . "/scormcontent/index.html");

        // if custom css is set, add it to the index.html
        if (strlen($css) > 0) {
            $index = str_replace("</style>", $css . PHP_EOL . "    </style>", $index);
        }

        // load and decode the courseData
        $start = strpos($index, "window.courseData = \"") + 21;
        $end = strpos($index,  "\";", $start);
        $in_data = substr($index, $start, $end - $start);
        $decoded_in = base64_decode($in_data);

        // find every iframe starting with $url
        $regexp = '#\\\"' . $matchurl . '(.*?)\"#';
        preg_match_all($regexp, $decoded_in, $matches);

        $finds = []; $replaces = [];

        // find and download all external assets matching the url
        foreach ($matches[0] as $match) {
            $raw = str_replace('\"', '', $match);
            $file_dl = file_get_contents($raw); // pull file from remote source (querystring ignored)
            if (($start = strpos($raw, "?")) !== false) {
                $raw = substr($raw, 0, $start);
            }
            $saveas = str_replace($url, '', $raw);
            $replace = str_replace($url, 'assets/', $raw);
            file_put_contents($dirname . "/scormcontent/assets/" . $saveas, $file_dl);
        }

        // copy all uploaded assets to the assets/ folder
        if (isset($_FILES['assets'])) {
            $assets = $_FILES['assets'];
            foreach ($assets['name'] as $key => $value) {
                if ($assets['error'][$key] == UPLOAD_ERR_OK) {
                    $tmp_name = $assets['tmp_name'][$key];
                    $name = $assets['name'][$key];
                    move_uploaded_file($tmp_name, $dirname . "/scormcontent/assets/" . $name);
                }
            }
        }

        // replace all urls with assets/
        $decoded_in = str_replace($url, 'assets/', $decoded_in);

        // file_put_contents($dirname . "/scormcontent/decoded.json", $decoded_in);
        $out_data = base64_encode($decoded_in);

        $index = str_replace($in_data, $out_data, $index);

        file_put_contents($dirname . "/scormcontent/index.html", $index);

        zip($dirname, $dirname . "-patched.zip");

        delTree($dirname);

        $results = "Finished. <a download href='" . $dirname . "-patched.zip'>" . $dirname . "-patched.zip</a>";
    
    }
}


?><!DOCTYPE html>
<html lang="en-US"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    

<title>Articulate Rise Packager | www.frumbert.org</title>
<meta name="generator" content="Jekyll v3.9.0">
<meta property="og:title" content="Articulate Rise Packager">
<meta property="og:locale" content="en_US">
<link rel="canonical" href="https://www.frumbert.org/risefix/">
<meta property="og:url" content="https://www.frumbert.org/risefix/">
<meta property="og:site_name" content="www.frumbert.org">
<meta name="twitter:card" content="summary">
<meta property="twitter:title" content="Articulate Rise Packager">
<script type="application/ld+json">
{"url":"https://www.frumbert.org/risefix/","@type":"WebSite","headline":"Articulate Rise Packager","name":"www.frumbert.org","@context":"https://schema.org"}</script>
<!-- End Jekyll SEO tag -->

    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link rel="preload" href="font.css" as="style" type="text/css" crossorigin="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#157878">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="stylesheet" href="https://frumbert.github.io/assets/css/style.css">
    <!-- start custom head snippets, customize with your own _includes/head-custom.html file -->

<!-- Setup Google Analytics -->



<!-- You can set your favicon here -->
<!-- link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" -->

<!-- end custom head snippets -->
</head>
<body>

    <a id="skip-to-content" href="#content">Skip to the content.</a>

    <header class="page-header" role="banner">
      <h1 class="project-name">Articulate Rise <span style='font-size:1rem'>re</span>Packager</h1>
      <h2 class="project-tagline">Takes a Rise package and repacks the course data with locally downloaded versions of all external assets matching the URL.</h2>
    </header>

    <main id="content" class="main-content" role="main">

<p>This utility is useful when you have embedded a custom HTML page as an iframe but want it to be included inside the package rather than relying on the external reference this creates in the Rise package. This tool will download that file, add it to the package assets folder then modify the URL so that it will load the local resource, then repackage the Zip for you.</p>
<p><b>Example:</b><blockquote>
    <b>https://frumbert.github.io/public/interaction.html?id=p4q2</b> is downloaded to <b>assets/interaction.html</b> and replaced in courseData with <b>assets/interaction.html?id=p4q2</b>
</blockquote></p>
<p><b>Note:</b> This utility does not recurse into the downloaded file, however you can also add multiple extra files to the assets folder - such as files referenced by your custom pages. You can also add any custom CSS which will be appended after other styles in the package index page.</p>
<?php if (!extension_loaded('zip')) die("<p>Zip extension not loaded.</p>"); ?>
<form action="index.php" method="post" enctype="multipart/form-data">
    <p>Rise Package: <input type="file" name="file">
    <p>Extra assets: <input type="file" name="assets[]" multiple> (will be added to <i>assets/</i> folder)
    <p>Find url: <input type="text" name="url" value="https://frumbert.github.io/public/" size="50"> (replace with assets/*)
    <p>Custom CSS (added to index.html): <textarea name="customcss" cols="40"></textarea>
    <p><input type="submit" value="Upload">
</form>

<p><?php echo $results; ?></p>

      <footer class="site-footer">
        
        <span class="site-footer-credits"><a href="https://frumbert.github.io/">Return to index</a>.</span>
      </footer>
    </main>
</body>
</html>
