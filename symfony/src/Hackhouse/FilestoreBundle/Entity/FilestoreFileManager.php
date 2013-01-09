<?

namespace Hackhouse\FilestoreBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use JMS\DiExtraBundle\Annotation\Inject;
use Hackhouse\Utils\Utils;
use Hackhouse\Abstracts\Service;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use AmazonS3;
use Hackhouse\FilestoreBundle\Entity\ThumbnailedFile;

class FilestoreFileManager extends Service
{
    const STORAGE_METHOD_S3 = 's3';
    const STORAGE_METHOD_FILESYSTEM = 'filesystem';

    protected $storageMethod;
    protected $bucketName;
    protected $awsRegion;
    protected $awsKey;
    protected $awsSecret;
    protected $awsObjectTTL;

    public function __construct($storageMethod, $bucketName, $awsKey, $awsSecret, $awsRegion, $awsObjectTTL){
        $this->storageMethod = $storageMethod;
        $this->bucketName = $bucketName;
        $this->awsKey = $awsKey;
        $this->awsSecret = $awsSecret;
        $this->awsRegion = $awsRegion;
        $this->awsObjectTTL = $awsObjectTTL;
    }

    private $validMimetypes = array(
        'image/png',
        'image/jpeg'
    );

    private $mimetypeToExtension = array(
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'audio/mpeg' => 'mp3'
    );

    private $validFileExtensions = array(
        'jpeg',
        'jpg',
        'png',
        'mp3'
    );

    public $imageMimeType = array(
        'image/gif',
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
        'image/bmp',
        'image/tiff'
    );

    public $audioMimeType = array(
        'audio/mpeg'
    );

    public function getExtensionForMimetype($mimetype){
        return $this->mimetypeToExtension[$mimetype];
    }


    public function validMimetype($mimeType){
        return in_array($mimeType, $this->validMimetypes) !== false;
    }

    public function validFileExtension($filename, $acceptedExtensions = null){
        $extension = $this->getFileExtension($filename);

        if ($acceptedExtensions == null){
            $acceptedExtensions = $this->validFileExtensions;
        }

        return in_array($extension, $acceptedExtensions) !== false;
    }

    public function isImageFile($type) {
        return in_array($type, $this->imageMimeType);
    }

    public function getFileExtension($filename){
        // get base name of the filename provided by user
        $filename = basename($filename);

        // break file into parts seperated by .
        $filename = explode('.', $filename);

        // take the last part of the file to get the file extension
        $filename = $filename[count($filename)-1];

        return strtolower($filename);
    }

    /**********************************
     * Filestore
     **********************************/

    public function permissionizeForFilesystemFilestore($path){
        chmod($path, 0777);
    }

    public function store($uploadedFilename, $path = null) {
        $filestoreFile = $this->createFilestoreFile($path);

        if ($this->getStorageMethod() == self::STORAGE_METHOD_S3){
            $this->storeOnS3($uploadedFilename, $filestoreFile);
        }
        else {
            $this->storeOnFilesystem($uploadedFilename, $filestoreFile);
        }

        return $filestoreFile;
    }

    public function storeFromTemporaryLocation($temporaryLocation, $path = null) {
        $filestoreFile = $this->createFilestoreFile($path);

        $s3 = $this->getS3ApiConnectionAndInfo();

        if (Utils::startsWith($temporaryLocation, 'https://s3.amazonaws.com/' . $s3['bucket'])){
            $sourcePath = $this->getObjectPathFromS3Url($temporaryLocation);
            $source = array(
                'bucket' => $s3['bucket'],
                'filename' => $sourcePath
            );

            $destination = array(
                'bucket' => $s3['bucket'],
                'filename' => $path
            );

            $result = $s3['conn']->copy_object($source, $destination, array(
                'acl' => AmazonS3::ACL_PUBLIC
            ));

            if (!$result->isOK()){
                throw new \Exception("Could not upload filestore file to S3");
            }
        }
        else {
            $tempLocation = tempnam('/tmp', 'twilioMp3-');
            file_put_contents($tempLocation, file_get_contents($temporaryLocation));

            $this->doStoreONS3($tempLocation, $path);
            unlink($tempLocation);
        }

        return $filestoreFile;
    }

    /**
     *
     * @param string $filename
     * @param string $path
     * @return FilestoreFile
     */
    public function createFilestoreFile($path = null){
        $filestoreFile = new FilestoreFile();

        if ($path){
            $path = str_replace(' ', '', trim(strtolower($path)));

            if (Utils::isEmptyStr($path)){
                throw new \Exception("Path is empty!");
            }

            $filestoreFile->setPath($path);
        }
        $this->getEntityManager()->persist($filestoreFile);
        $this->getEntityManager()->flush();

        return $filestoreFile;
    }

    private function storeOnFilesystem($uploadedFilename, FilestoreFile $filestoreFile){
        $directory = $filestoreFile->getFilesystemDirectory();

        if (!file_exists($directory)){
            mkdir($directory, 0777, true);
            $this->permissionizeForFilesystemFilestore($directory);
            $this->logInfo('Created directory ' . $directory);
        }

        copy($uploadedFilename, $filestoreFile->getFilesystemPath());
        self::permissionizeForFilesystemFilestore($filestoreFile->getFilesystemPath());

        //die if the copy did not work
        if (!file_exists($filestoreFile->getFilesystemPath())) {
            $this->delete($filestoreFile);
            throw new \Exception('The upload did not succeed!  The file wasnt copied for some reason.  Check write permissions on the filestore.');
        }
    }

    private function storeOnS3($uploadedFilename, FilestoreFile $file){
        $this->doStoreOnS3($uploadedFilename, $file->getPath());
    }

    public function doStoreONS3($uploadedFilename, $s3Path, $options = array()){
        $s3 = $this->getS3ApiConnectionAndInfo();

        $defaultOptions = array(
            'fileUpload' => $uploadedFilename,
            'acl' => AmazonS3::ACL_PUBLIC
        );

        $result = $s3['conn']->create_object($s3['bucket'], $s3Path, array_merge($defaultOptions, $options));

        if (!$result->isOK()){
            throw new \Exception("Could not upload filestore file to S3");
        }
    }


    public function getFileAbsoluteUrl($file){
        return $file ? $this->getS3ObjectUrl($file->getPath()) : null;
    }

    public function getS3ObjectUrl($objectPath){
        $objectPath = trim($objectPath, ' /');
        $s3 = $this->getS3ApiConnectionAndInfo();
        return 'https://s3.amazonaws.com/' . $s3['bucket'] . '/' . $objectPath;
    }

    public function getObjectPathFromS3Url($url){
        $s3 = $this->getS3ApiConnectionAndInfo();
        return substr($url, strlen('https://s3.amazonaws.com/' . $s3['bucket'] . '/'));
    }


    public function getS3ApiConnectionAndInfo(){
        $s3 = new AmazonS3(array(
            'key' => $this->getAwsKey(),
            'secret' => $this->getAwsSecret()
        ));

        return array('conn' => $s3, 'bucket' => $this->getBucketName(), 'region' => $this->getAwsRegion(),
            'object_ttl' => $this->getAwsObjectTTL());
    }

    public function delete(FilestoreFile $file){
        if ($this->getStorageMethod() == self::STORAGE_METHOD_S3){
            $path = $file->getPath();

            $s3 = $this->getS3ApiConnectionAndInfo();
            $s3['conn']->delete_object($s3['bucket'], $path);

            if ($s3['conn']->if_object_exists($s3['bucket'], $path)){
                throw new \Exception('FilestoreFile could not be deleted from S3');
            }
        }
        else {
            unlink($file->getFilesystemPath());
        }

        $this->getEntityManager()->remove($file);
        $this->getEntityManager()->flush();

    }

    public function getThumbnailUrl(ThumbnailedFile $file, $size = null){
        $size = strtolower(trim($size));
        switch($size){
            case 'small': return $this->getS3ObjectUrl($file->getSmall()->getPath());
            case 'medium': return $this->getS3ObjectUrl($file->getMedium()->getPath());
            case 'large': return $this->getS3ObjectUrl($file->getLarge()->getPath());
            default: return $this->getS3ObjectUrl($file->getTarget()->getPath());
        }
    }

    public function createThumbnailFile($pathToImage, $basePath, $extension = 'jpg', $sizes = array(
        'large' => ThumbnailedFile::PROFILE_LARGE,
        'medium' => ThumbnailedFile::PROFILE_MEDIUM,
        'small' => ThumbnailedFile::PROFILE_SMALL
    ), $enlarge = true){
        $thumbnailedFile = new ThumbnailedFile();

        $target = $this->store($pathToImage, "$basePath.$extension");
        $thumbnailedFile->setTarget($target);

        list($imageWidth, $imageHeight, $imageType) = getimagesize($pathToImage);
        $largerDim = $imageWidth >= $imageHeight ? $imageWidth : $imageHeight;

        if ($largerDim > $sizes['large'] || ($largerDim <= $sizes['large'] && $enlarge)){
            $large = $this->createThumbnailFilestoreFile($pathToImage, $basePath, $extension, 'large', $sizes['large']);
        }
        else {
            $large = $target;
        }
        $thumbnailedFile->setLarge($large);

        if ($largerDim > $sizes['medium'] || ($largerDim <= $sizes['medium'] && $enlarge)){
            $medium = $this->createThumbnailFilestoreFile($pathToImage, $basePath, $extension, 'medium', $sizes['medium']);
        }
        else {
            $medium = $target;
        }
        $thumbnailedFile->setMedium($medium);

        if ($largerDim > $sizes['small'] || ($largerDim <= $sizes['small'] && $enlarge)){
            $small = $this->createThumbnailFilestoreFile($pathToImage, $basePath, $extension, 'small', $sizes['small']);
        }
        else {
            $small = $target;
        }
        $thumbnailedFile->setSmall($small);

        $this->getEntityManager()->persist($thumbnailedFile);
        $this->getEntityManager()->flush();

        return $thumbnailedFile;

    }

    /**
     * @param $pathToImage
     * @param $basePath
     * @param $extension
     * @param $type
     * @param $largestDim
     * @return bool|FilestoreFile
     */
    private function createThumbnailFilestoreFile($pathToImage, $basePath, $extension, $type, $largestDim){
        $this->scaleFileToMaxDimension($pathToImage, "$pathToImage-$type.$extension", $largestDim);
        $newFilestoreFile = $this->store("$pathToImage-$type.$extension", "$basePath-$type.$extension");
        return $newFilestoreFile;
    }

    public function scaleFileToMaxDimension($pathToImage, $outPath, $largestDim){
        $imagick = new \Imagick();
        $imagick->readImage($pathToImage);
        $width = $imagick->getImageWidth();
        $height = $imagick->getImageHeight();

        if ($width >= $height) {
            $imagick->scaleImage($largestDim, 0);
        }
        else{
            $imagick->scaleImage(0, $largestDim);
        }

        $imagick->setImageFormat( "jpg" );
        $imagick->writeImage($outPath);
        $imagick->destroy();
    }

    public function setAwsKey($awsKey)
    {
        $this->awsKey = $awsKey;
    }

    public function getAwsKey()
    {
        return $this->awsKey;
    }

    public function setAwsRegion($awsRegion)
    {
        $this->awsRegion = $awsRegion;
    }

    public function getAwsRegion()
    {
        return $this->awsRegion;
    }

    public function setAwsSecret($awsSecret)
    {
        $this->awsSecret = $awsSecret;
    }

    public function getAwsSecret()
    {
        return $this->awsSecret;
    }

    public function setAwsObjectTTL($awsObjectTTL)
    {
        $this->awsObjectTTL = $awsObjectTTL;
    }

    public function getAwsObjectTTL()
    {
        return $this->awsObjectTTL;
    }

    public function setBucketName($bucketName)
    {
        $this->bucketName = $bucketName;
    }

    public function getBucketName()
    {
        return $this->bucketName;
    }

    public function setStorageMethod($storageMethod)
    {
        $this->storageMethod = $storageMethod;
    }

    public function getStorageMethod()
    {
        return $this->storageMethod;
    }

    public function squareImage($downloadPath){
        // Get dimensions of existing image
        $image = getimagesize($downloadPath);

        // Check for valid dimensions
        if( $image[0] <= 0 || $image[1] <= 0 ) return false;

        // Determine format from MIME-Type
        $image['format'] = strtolower(preg_replace('/^.*?\//', '', $image['mime']));

        // Import image
        switch( $image['format'] ) {
            case 'jpg':
            case 'jpeg':
                $image_data = imagecreatefromjpeg($downloadPath);
                break;
            case 'png':
                $image_data = imagecreatefrompng($downloadPath);
                break;
            case 'gif':
                $image_data = imagecreatefromgif($downloadPath);
                break;
            default:
                // Unsupported format
                return false;
                break;
        }

        // Verify import
        if( $image_data == false ) return false;

        // Calculate measurements
        if( $image[0] & $image[1] ) {
            // For landscape images
            $x_offset = ($image[0] - $image[1]) / 2;
            $y_offset = 0;
            $square_size = $image[0] - ($x_offset * 2);
        } else {
            // For portrait and square images
            $x_offset = 0;
            $y_offset = ($image[1] - $image[0]) / 2;
            $square_size = $image[1] - ($y_offset * 2);
        }

        // Resize and crop
        $canvas = imagecreatetruecolor($square_size, $square_size);
        if( imagecopyresampled(
            $canvas,
            $image_data,
            0,
            0,
            $x_offset,
            $y_offset,
            $square_size,
            $square_size,
            $square_size,
            $square_size
        )) {
            return imagejpeg($canvas, $downloadPath, 90);
        } else {
            return false;
        }
    }

}
