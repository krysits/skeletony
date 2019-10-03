<?php
namespace Krysits\Models;

use Krysits\Model;

class Book extends Model
{
	// variables
	public $id;
	public $bid;
	public $title;
	public $subtitle;
	public $author;
	public $cover;
	public $isbn;
	public $publisher;
	public $description;
	public $year;
	public $pages;
	public $created_at; //date('Y-m-d H:i:s');
	public $updated_at; //date('Y-m-d H:i:s');
	public $deleted_at; //date('Y-m-d H:i:s');
	public $fulltext;

	public $_showId = true;
	public $_schema = 'public';

	// constructor
	public function __construct($book_id = 0)
	{
		parent::__construct();

		$this->setTable('books');

		if($book_id) {
			$this->getRecord($book_id);
		}
	}
};