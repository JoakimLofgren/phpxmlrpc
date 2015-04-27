<?php
/**
 * Makefile for phpxmlrpc library.
 * To be used with the Pake tool: https://github.com/indeyets/pake/wiki
 *
 * @copyright (c) 2015 G. Giunta
 *
 * @todo allow user to specify release number and tag/branch to use
 * @todo !important allow user to specify location of docbook xslt instead of the one installed via composer
 */

namespace PhpXmlRpc {

class Builder
{
    protected static $buildDir = 'build';
    protected static $libVersion;
    protected static $sourceBranch = 'master';
    protected static $tools = array(
        'zip' => 'zip',
        'fop' => 'fop',
        'php' => 'php'
    );

    public static function libVersion()
    {
        return self::$libVersion;
    }

    public static function buildDir()
    {
        return self::$buildDir;
    }

    public static function workspaceDir()
    {
        return self::buildDir().'/workspace';
    }

    /// most likely things will break if this one is moved outside of BuildDir
    public static function distDir()
    {
        return self::buildDir().'/xmlrpc-'.self::libVersion();
    }

    /// these will be generated in BuildDir
    public static function distFiles()
    {
        return array(
            'xmlrpc-'.self::libVersion().'.tar.gz',
            'xmlrpc-'.self::libVersion().'.zip',
        );
    }

    public static function sourceRepo()
    {
        return 'https://github.com/gggeek/phpxmlrpc';
    }

    /// @todo move git branch to be a named option?
    public static function getOpts($args=array(), $cliOpts=array())
    {
        if (count($args) < 1)
            throw new \Exception('Missing library version argument');
        self::$libVersion = $args[0];
        if (count($args) > 1)
            self::$sourceBranch = $args[1];

        foreach (self::$tools as $name => $binary) {
            if (isset($cliOpts[$name])) {
                self::$tools[$name] = $cliOpts[$name];
            }
        }

        //pake_echo('---'.self::$libVersion.'---');
    }

    public static function tool($name)
    {
        return self::$tools[$name];
    }

    /**
     * @param string $inFile
     * @param string $xssFile
     * @param string $outFileOrDir
     * @throws \Exception
     */
    public static function applyXslt($inFile, $xssFile, $outFileOrDir)
    {

        if (!file_exists($inFile)) {
            throw new \Exception("File $inFile cannot be found");
        }
        if (!file_exists($xssFile)) {
            throw new \Exception("File $xssFile cannot be found");
        }

        // Load the XML source
        $xml = new \DOMDocument();
        $xml->load($inFile);
        $xsl = new \DOMDocument();
        $xsl->load($xssFile);

        // Configure the transformer
        $processor = new \XSLTProcessor();
        if (version_compare(PHP_VERSION, '5.4', "<")) {
            if (defined('XSL_SECPREF_WRITE_FILE')) {
                ini_set("xsl.security_prefs", XSL_SECPREF_CREATE_DIRECTORY | XSL_SECPREF_WRITE_FILE);
            }
        } else {
            // the php online docs only mention setSecurityPrefs, but somehow some installs have setSecurityPreferences...
            if (method_exists('XSLTProcessor', 'setSecurityPrefs')) {
                $processor->setSecurityPrefs(XSL_SECPREF_CREATE_DIRECTORY | XSL_SECPREF_WRITE_FILE);
            } else {
                $processor->setSecurityPreferences(XSL_SECPREF_CREATE_DIRECTORY | XSL_SECPREF_WRITE_FILE);
            }
        }
        $processor->importStyleSheet($xsl); // attach the xsl rules

        if (is_dir($outFileOrDir)) {
            if (!$processor->setParameter('', 'base.dir', realpath($outFileOrDir))) {
                echo "setting param base.dir KO\n";
            }
        }

        $out = $processor->transformToXML($xml);

        if (!is_dir($outFileOrDir)) {
            file_put_contents($outFileOrDir, $out);
        }
    }

    public static function highlightPhpInHtml($content)
    {
        $startTag = '<pre class="programlisting">';
        $endTag = '</pre>';

        //$content = file_get_contents($inFile);
        $last = 0;
        $out = '';
        while (($start = strpos($content, $startTag, $last)) !== false) {
            $end = strpos($content, $endTag, $start);
            $code = substr($content, $start + strlen($startTag), $end - $start - strlen($startTag));
            if ($code[strlen($code) - 1] == "\n") {
                $code = substr($code, 0, -1);
            }

            $code = str_replace(array('&gt;', '&lt;'), array('>', '<'), $code);
            $code = highlight_string('<?php ' . $code, true);
            $code = str_replace('<span style="color: #0000BB">&lt;?php&nbsp;<br />', '<span style="color: #0000BB">', $code);

            $out = $out . substr($content, $last, $start + strlen($startTag) - $last) . $code . $endTag;
            $last = $end + strlen($endTag);
        }
        $out .= substr($content, $last, strlen($content));

        return $out;
    }
}

}

namespace {

use PhpXmlRpc\Builder;

function run_default($task=null, $args=array(), $cliOpts=array())
{
    echo "Syntax: pake {\$pake-options} \$task \$lib-version [\$git-tag] {\$task-options}\n";
    echo "\n";
    echo "  Run 'pake help' to list all pake options\n";
    echo "  Run 'pake -T' to list all available tasks\n";
    echo "  Task options:\n";
    echo "      --php=\$php";
    echo "      --fop=\$fop";
    echo "      --zip=\$zip";
}

function run_getopts($task=null, $args=array(), $cliOpts=array())
{
    Builder::getOpts($args, $cliOpts);
}

/**
 * Downloads source code in the build workspace directory, optionally checking out the given branch/tag
 */
function run_init($task=null, $args=array(), $cliOpts=array())
{
    // download the current version into the workspace
    $targetDir = Builder::workspaceDir();
    $targetBranch = 'php53';

    // check if workspace exists and is not already set to the correct repo
    if (is_dir($targetDir) && pakeGit::isRepository($targetDir)) {
        $repo = new pakeGit($targetDir);
        $remotes = $repo->remotes();
        if (trim($remotes['origin']['fetch']) != Builder::sourceRepo()) {
            throw new Exception("Directory '$targetDir' exists and is not linked to correct git repo");
        }

        /// @todo should we not just fetch instead?
        $repo->pull();
    } else {
        pake_mkdirs(dirname($targetDir));
        $repo = pakeGit::clone_repository(Builder::sourceRepo(), Builder::workspaceDir());
    }

    $repo->checkout($targetBranch);
}

/**
 * Runs all the build steps.
 *
 * (does nothing by itself, as all the steps are managed via task dependencies)
 */
function run_build($task=null, $args=array(), $cliOpts=array())
{
}

function run_clean_doc()
{
    pake_remove_dir(Builder::workspaceDir().'/doc/out');
    pake_remove_dir(Builder::workspaceDir().'/doc/javadoc-out');
}

/**
 * Generates documentation in all formats
 */
function run_doc($task=null, $args=array(), $cliOpts=array())
{
    $docDir = Builder::workspaceDir().'/doc';

    // API docs from phpdoc comments using phpdocumentor
    $cmd = Builder::tool('php');
    pake_sh("$cmd vendor/phpdocumentor/phpdocumentor/bin/phpdoc run -d ".Builder::workspaceDir().'/src'." -t ".Builder::workspaceDir().'/doc/javadoc-out --title PHP-XMLRPC');

    # Jade cmd yet to be rebuilt, starting from xml file and putting output in ./out dir, e.g.
    #	jade -t xml -d custom.dsl xmlrpc_php.xml
    #
    # convertdoc command for xmlmind xxe editor
    #	convertdoc docb.toHTML xmlrpc_php.xml -u out
    #
    # saxon + xerces xml parser + saxon extensions + xslthl: adds a little syntax highligting
    # (bold and italics only, no color) for php source examples...
    #	java \
    #	-classpath c:\programmi\saxon\saxon.jar\;c:\programmi\saxon\xslthl.jar\;c:\programmi\xerces\xercesImpl.jar\;C:\htdocs\xmlrpc_cvs\docbook-xsl\extensions\saxon65.jar \
    #	-Djavax.xml.parsers.DocumentBuilderFactory=org.apache.xerces.jaxp.DocumentBuilderFactoryImpl \
    #	-Djavax.xml.parsers.SAXParserFactory=org.apache.xerces.jaxp.SAXParserFactoryImpl \
    #	-Dxslthl.config=file:///c:/htdocs/xmlrpc_cvs/docbook-xsl/highlighting/xslthl-config.xml \
    #	com.icl.saxon.StyleSheet -o xmlrpc_php.fo.xml xmlrpc_php.xml custom.fo.xsl use.extensions=1

    pake_mkdirs($docDir.'/out');

    // HTML files from docbook

    Builder::applyXslt($docDir.'/xmlrpc_php.xml', $docDir.'/custom.xsl', $docDir.'/out/');
    // post process html files to highlight php code samples
    foreach(pakeFinder::type('file')->name('*.html')->in($docDir) as $file)
    {
        file_put_contents($file, Builder::highlightPhpInHtml(file_get_contents($file)));
    }

    // PDF file from docbook

    // convert to fo and then to pdf using apache fop
    Builder::applyXslt($docDir.'/xmlrpc_php.xml', $docDir.'/custom.fo.xsl', $docDir.'/xmlrpc_php.fo.xml');
    $cmd = Builder::tool('fop');
    pake_sh("$cmd $docDir/xmlrpc_php.fo.xml $docDir/xmlrpc_php.pdf");
    unlink($docDir.'/xmlrpc_php.fo.xml');
}

function run_clean_dist()
{
    pake_remove_dir(Builder::distDir());
    $finder = pakeFinder::type('file')->name(Builder::distFiles());
    pake_remove($finder, Builder::buildDir());
}

/**
 * Creates the tarballs for a release
 */
function run_dist($task=null, $args=array(), $cliOpts=array())
{
    // copy workspace dir into dist dir, without git
    pake_mkdirs(Builder::distDir());
    $finder = pakeFinder::type('any')->ignore_version_control();
    pake_mirror($finder, realpath(Builder::workspaceDir()), realpath(Builder::distDir()));

    // remove unwanted files from dist dir

    // also: do we still need to run dos2unix?

    // create tarballs
    $cwd = getcwd();
    chdir(dirname(Builder::distDir()));
    foreach(Builder::distFiles() as $distFile) {
        // php can not really create good zip files via phar: they are not compressed!
        if (substr($distFile, -4) == '.zip') {
            $cmd = Builder::tool('zip');
            $extra = '-9 -r';
            pake_sh("$cmd $distFile $extra ".basename(Builder::distDir()));
        }
        else {
            $finder = pakeFinder::type('any')->pattern(basename(Builder::distDir()).'/**');
            // see https://bugs.php.net/bug.php?id=58852
            $pharFile = str_replace(Builder::libVersion(), '_LIBVERSION_', $distFile);
            pakeArchive::createArchive($finder, '.', $pharFile);
            rename($pharFile, $distFile);
        }
    }
    chdir($cwd);
}

/**
 * Cleans up the build directory
 * @todo 'make clean' usually just removes the results of the build, distclean removes all but sources
 */
function run_clean($task=null, $args=array(), $cliOpts=array())
{
    pake_remove_dir(Builder::buildDir());
}

// helper task: display help text
pake_task( 'default' );
// internal task: parse cli options
pake_task('getopts');
pake_task('init', 'getopts');
pake_task('doc', 'getopts', 'init', 'clean-doc');
pake_task('build', 'getopts', 'init', 'doc');
pake_task('dist', 'getopts', 'init', 'build', 'clean-dist');
pake_task('clean-doc', 'getopts');
pake_task('clean-dist', 'getopts');
pake_task('clean', 'getopts');

}
