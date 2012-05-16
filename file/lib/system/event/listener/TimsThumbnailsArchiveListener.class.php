<?php
namespace wcf\system\event\listener;

/**
 * Generates thumbnails for archives.
 *
 * @author 	Tim Düsterhus
 * @copyright	2012 Tim Düsterhus
 * @license	Creative Commons Attribution-NonCommercial-ShareAlike <http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode>
 * @package	be.bastelstu.wcf.thumbnailGenerators.archive
 * @subpackage	system.event.listener
 */
class TimsThumbnailsArchiveListener implements \wcf\system\event\IEventListener {
	private $eventObj = null;
	
	/**
	 * @see	\wcf\system\event\IEventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		$this->eventObj = $eventObj;
		switch ($eventName) {
			case 'checkThumbnail':
			case 'generateThumbnail':
				$this->$eventName();
			default:
				return;
		}
	}
	
	/**
	 * Registers the files for thumbnail-creation
	 */
	public function checkThumbnail() {
		switch ($this->eventObj->eventAttachment->fileType) {
			case 'application/zip':
			case 'application/x-tar':
				$this->eventObj->eventData['hasThumbnail'] = true;
		}
	}
	
	/**
	 * Actually generate the thumbnail.
	 */
	public function generateThumbnail() {
		switch ($this->eventObj->eventAttachment->fileType) {
			case 'application/zip':
				$file = new \wcf\system\io\Zip($this->eventObj->eventAttachment->getLocation());
			break;
			case 'application/x-tar':
				$file = new \wcf\system\io\Tar($this->eventObj->eventAttachment->getLocation());
			break;
			default:
				return;
		}
		$files = $file->getContentList();
		
		// load data
		$tinyAdapter = \wcf\system\image\ImageHandler::getInstance()->getAdapter();
		$adapter = \wcf\system\image\ImageHandler::getInstance()->getAdapter();
		
		// plug the sheeps onto the wooden sticks and submerge them in the color
		$tinyAdapter->createEmptyImage(144, 144);
		$adapter->createEmptyImage(ATTACHMENT_THUMBNAIL_WIDTH, ATTACHMENT_THUMBNAIL_HEIGHT);
		$tinyAdapter->setColor(0x00, 0x00, 0x00);
		$adapter->setColor(0x00, 0x00, 0x00);
		
		$i = 1;
		foreach ($files as $file) {
			// tabs cannot be displayed with gdlib
			$file = str_replace("\t", "    ", $file['filename']);
			
			$tinyAdapter->drawText($file, 5, $i * 10);
			$adapter->drawText($file, 5, $i * 10);
			$i++;
		}
		
		// and create the images
		$tinyThumbnailLocation = $this->eventObj->eventAttachment->getTinyThumbnailLocation();
		$thumbnailLocation = $this->eventObj->eventAttachment->getThumbnailLocation();
		
		$tinyAdapter->writeImage($tinyThumbnailLocation.'.png');
		rename($tinyThumbnailLocation.'.png', $tinyThumbnailLocation);
		$adapter->writeImage($thumbnailLocation.'.png');
		rename($thumbnailLocation.'.png', $thumbnailLocation);
		
		// calculate the thumbnail data
		$updateData = array();
		if (file_exists($tinyThumbnailLocation) && ($imageData = @getImageSize($tinyThumbnailLocation)) !== false) {
			$updateData['tinyThumbnailType'] = $imageData['mime'];
			$updateData['tinyThumbnailSize'] = @filesize($tinyThumbnailLocation);
			$updateData['tinyThumbnailWidth'] = $imageData[0];
			$updateData['tinyThumbnailHeight'] = $imageData[1];
		}
		
		if (file_exists($thumbnailLocation) && ($imageData = @getImageSize($thumbnailLocation)) !== false) {
			$updateData['thumbnailType'] = $imageData['mime'];
			$updateData['thumbnailSize'] = @filesize($thumbnailLocation);
			$updateData['thumbnailWidth'] = $imageData[0];
			$updateData['thumbnailHeight'] = $imageData[1];
		}
		
		$this->eventObj->eventData = $updateData;
	}
}
