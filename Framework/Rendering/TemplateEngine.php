<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Rendering;

class TemplateEngine
{
    private string $baseDir;

    private ProcessorIncludes $processorIncludes;
    private ProcessorFlattenParams $processFlattenParams;
    private ProcessorForEach $processorForEach;
    private ProcessorIfBlocks $processorIfBlocks;
    private ProcessorVariables $processorVariables;
    private ProcessorKeyPath $processorKeyPath;
    private ProcessorTernary $processorTernary;

    public function __construct(
        ProcessorIncludes $processorIncludes,
        ProcessorFlattenParams $processFlattenParams,
        ProcessorForEach $processorForEach,
        ProcessorIfBlocks $processorIfBlocks,
        ProcessorVariables $processorVariables,
        ProcessorKeyPath $processorKeyPath,
        ProcessorTernary $processorTernary
    )
    {
        $this->processorIncludes = $processorIncludes;
        $this->processFlattenParams = $processFlattenParams;
        $this->processorForEach = $processorForEach;
        $this->processorIfBlocks = $processorIfBlocks;
        $this->processorVariables = $processorVariables;
        $this->processorKeyPath = $processorKeyPath;
        $this->processorTernary = $processorTernary;
    }

    public function processTemplate(string $content, array $params): string
    {
        $content = $this->processorIncludes->process($content, $this->baseDir);
        $flatParams = $this->processFlattenParams->process($params);

        $content = $this->processorForEach->process($content, $params);
        $content = $this->processorTernary->process($content, $flatParams);
        $content = $this->processorVariables->process($content, $flatParams);
        $content = $this->processorIfBlocks->process($content, $flatParams);
        $content = $this->processorKeyPath->process($content, $flatParams);

        return $content;
    }

    public function setBaseDir(string $path): void
    {
        $this->baseDir = rtrim($path, '/') . '/';
    }
}
