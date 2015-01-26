<?php
namespace Pixmicat\FileIo;

/**
 * 抽象 FileIO + IFS。
 */
abstract class AbstractIfsFileIO extends AbstractFileIO
{
    /** @var IndexFS */
    protected $IFS;

    public function __construct($parameter, $ENV)
    {
        parent::__construct();

        $this->IFS = new IndexFS($ENV['IFS.LOG']);
        $this->IFS->openIndex();
        register_shutdown_function(array($this, 'saveIndex'));
    }

    /**
     * 儲存索引檔
     */
    public function saveIndex()
    {
        $this->IFS->saveIndex();
    }

    public function imageExists($imgname)
    {
        return $this->IFS->beRecord($imgname);
    }

    public function getImageFilesize($imgname)
    {
        $rc = $this->IFS->getRecord($imgname);
        if (!\is_null($rc)) {
            return $rc['imgSize'];
        }
        return 0;
    }

    public function resolveThumbName($thumbPattern)
    {
        return $this->IFS->findThumbName($thumbPattern);
    }

    protected function getCurrentStorageSizeNoCache()
    {
        return $this->IFS->getCurrentStorageSize();
    }
}
