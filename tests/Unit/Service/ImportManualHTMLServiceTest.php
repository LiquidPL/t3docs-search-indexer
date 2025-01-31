<?php

namespace App\Tests\Unit\Service;

use App\Dto\Manual;
use App\Repository\ElasticRepository;
use App\Service\ImportManualHTMLService;
use App\Service\ParseDocumentationHTMLService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ImportManualHTMLServiceTest extends TestCase
{
    /**
     * @test
     */
    public function allowsToDeleteManual()
    {
        $manual = $this->prophesize(Manual::class)->reveal();
        $repo = $this->prophesize(ElasticRepository::class);

        $subject = new ImportManualHTMLService(
            $repo->reveal(),
            $this->prophesize(ParseDocumentationHTMLService::class)->reveal(),
            $this->prophesize(EventDispatcherInterface::class)->reveal()
        );

        $repo->deleteByManual($manual)->shouldBeCalledTimes(1);

        $subject->deleteManual($manual);
    }

    /**
     * @test
     */
    public function allowsImportOfManual()
    {
        $finder = $this->prophesize(Finder::class);

        $manual = $this->prophesize(Manual::class);
        $manual->getTitle()->willReturn('typo3/cms-core');
        $manual->getType()->willReturn('c');
        $manual->getVersion()->willReturn('main');
        $manual->getLanguage()->willReturn('en-us');
        $manual->getSlug()->willReturn('slug');
        $manual->getFilesWithSections()->willReturn($finder->reveal());
        $manualRevealed = $manual->reveal();

        $repo = $this->prophesize(ElasticRepository::class);
        $parser = $this->prophesize(ParseDocumentationHTMLService::class);

        $file = $this->prophesize(SplFileInfo::class);
        $file->getRelativePathname()->willReturn('c/typo3/cms-core/main/en-us');
        $fileRevealed = $file->reveal();

        $section1 = [
            'fragment' => 'features-and-basic-concept',
            'snippet_title' => 'Features and Basic Concept',
            'snippet_content' => 'The main goal for this blog extension was to use TYPO3s core concepts and elements to provide a full-blown blog that users of TYPO3 can instantly understand and use.'
        ];
        $section2 = [
            'fragment' => 'pages-as-blog-entries',
            'snippet_title' => 'Pages as blog entries',
            'snippet_content' => 'Blog entries are simply pages with a special page type blog entry and can be created and edited via the well-known page module. Creating new entries is as simple as dragging a new entry into the page tree.'
        ];


        $parser->getSectionsFromFile($fileRevealed)->willReturn([$section1, $section2]);
        $finder->getIterator()->willReturn(new \ArrayObject([$fileRevealed]));

        $subject = new ImportManualHTMLService($repo->reveal(), $parser->reveal(), $this->prophesize(EventDispatcherInterface::class)->reveal());

        $repo->addOrUpdateDocument([
            'fragment' => 'features-and-basic-concept',
            'snippet_title' => 'Features and Basic Concept',
            'snippet_content' => 'The main goal for this blog extension was to use TYPO3s core concepts and elements to provide a full-blown blog that users of TYPO3 can instantly understand and use.',
            'manual_title' => 'typo3/cms-core',
            'manual_type' => 'c',
            'manual_version' => 'main',
            'manual_language' => 'en-us',
            'manual_slug' => 'slug',
            'relative_url' => 'c/typo3/cms-core/main/en-us',
            "content_hash" => "718ab540920b06f925f6ef7a34d6a5c7",
        ])->shouldBeCalledTimes(1);
        $repo->addOrUpdateDocument([
            'fragment' => 'pages-as-blog-entries',
            'snippet_title' => 'Pages as blog entries',
            'snippet_content' => 'Blog entries are simply pages with a special page type blog entry and can be created and edited via the well-known page module. Creating new entries is as simple as dragging a new entry into the page tree.',
            'manual_title' => 'typo3/cms-core',
            'manual_type' => 'c',
            'manual_version' => 'main',
            'manual_language' => 'en-us',
            'manual_slug' => 'slug',
            'relative_url' => 'c/typo3/cms-core/main/en-us',
            "content_hash" => "a248b5d0798e30e7c9389b81b499c5d9",
        ])->shouldBeCalledTimes(1);

        $subject->importManual($manualRevealed);
    }
}
