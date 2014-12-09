<?php
abstract class CApplication
{
	private $_components=array();


	/**
	 * Returns the database connection component.
	 * @return CDbConnection the database connection
	 */
	public function getDb()
	{
		return $this->getComponent('db');
	}


	/**
	 * Retrieves the named application component.
	 * @param string $id application component ID (case-sensitive)
	 * @param boolean $createIfNull whether to create the component if it doesn't exist yet.
	 * @return IApplicationComponent the application component instance, null if the application component is disabled or does not exist.
	 * @see hasComponent
	 */
	public function getComponent($id,$createIfNull=true)
	{
		if(isset($this->_components[$id]))
			return $this->_components[$id];
		elseif(isset($this->_componentConfig[$id]) && $createIfNull)
		{
			$config=$this->_componentConfig[$id];
			if(!isset($config['enabled']) || $config['enabled'])
			{
				Yii::trace("Loading \"$id\" application component",'system.CModule');
				unset($config['enabled']);
				$component=Yii::createComponent($config);
				$component->init();
				return $this->_components[$id]=$component;
			}
		}
	}
	/**
	 * Registers the core application components.
	 * @see setComponents
	 */
	protected function registerCoreComponents()
	{
		$components=array(
			'db'=>array(
				'class'=>'CDbConnection',
			)
		);
		$this->setComponents($components);
	}


	public function setComponents($components,$merge=true)
	{
		foreach($components as $id=>$component)
			$this->setComponent($id,$component,$merge);
	}

	public function setComponent($id, $component, $merge)
	{
		$this->_components[$id] = $component;
	}
}