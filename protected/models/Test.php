<?php
class Test extends CActiveRecord
{
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	* @return array relational rules.
	*/
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'bank_account_calculate_type';
	}
 
}
?>