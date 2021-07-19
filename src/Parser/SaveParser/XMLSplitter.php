<?php

declare(strict_types=1);

use AppUtils\FileHelper;

/**
 * Kept for historical purposes: this was used before the
 * semi-XML parsing to split the savegame file into smaller
 * XML chunks to make them easier to parse.
 *
 * (was part of the SaveParser class)
 */
class XMLSplitter
{
    private function splitFile() : void
    {
        $this->log(sprintf('Splitting the XML file [%s.].', $this->saveName));

        if($this->isUnpacked()) {
            $this->log('Already split, skipping.');
            return;
        }

        $this->log('Extracting data.');

        $this->createFolders();

        $tags = array(
            'info',
            'economylog',
            'stats',
            'universe',
            'log',
            'messages',
            'missions',
        );

        $ignore = array(
            'script',
            'md',
            'shadervalues',
            'shaderparams',
            'orders',
            'terraforming',
            'aidirector'
        );

        $openTags = array();
        $closeTags = array();
        foreach($tags as $tagName)
        {
            $openTags['<'.$tagName.'>'] = $tagName;
            $closeTags['</'.$tagName.'>'] = $tagName;
        }

        $ignoreOpen = array();
        $ignoreClose = array();
        foreach ($ignore as $tagName)
        {
            $ignoreOpen['<'.$tagName.'>'] = $tagName;
            $ignoreClose['</'.$tagName.'>'] = $tagName;
        }

        $xmlFolder = $this->outputFolder.'/xml';
        FileHelper::createFolder($xmlFolder);

        $in = fopen($this->xmlFile, "r+");
        $activeIgnore = false;
        $activeTag = null;
        $activeFile = null;

        while (($line = stream_get_line($in, 1024 * 1024, "\n")) !== false)
        {
            $line = trim($line);

            // Only process tags to ignore when there is no tag already
            // being captured.
            if($activeTag === null)
            {
                if ($activeIgnore === true && isset($ignoreClose[$line]))
                {
                    $activeIgnore = false;
                    continue;
                }

                if ($activeIgnore === true)
                {
                    continue;
                }

                if (isset($ignoreOpen[$line]) || substr($line, 0, 13) === '<terraforming')
                {
                    $activeIgnore = true;
                    continue;
                }
            }

            // Found an opening tag.
            if(isset($openTags[$line]))
            {
                $activeTag = $openTags[$line];
                $path = $xmlFolder.'/'.$activeTag.'.xml';
                FileHelper::deleteFile($path);
                $activeFile = fopen($path, 'x');
                fwrite($activeFile, $line.PHP_EOL);

                unset($openTags[$line]);
                continue;
            }

            if (isset($closeTags[$line]))
            {
                if(is_resource($activeFile)) {
                    fwrite($activeFile, $line.PHP_EOL);
                    fclose($activeFile);
                }

                $activeTag = null;
                unset($closeTags[$line]);
                continue;
            }

            if($activeTag === null)
            {
                continue;
            }

            fwrite($activeFile, $line.PHP_EOL);
        }

        fclose($in);

        $this->log('Done extracting.');
    }
}
