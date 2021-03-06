<?php

namespace Application\Service;

use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Resource\GenericResource;

/**
 * service: permission_service
 */
class Permission extends BaseService
{
    const RESOURCE_MANAGE_USER_TYPE = 'manage-user-resource';
	const RESOURCE_INVITE = 'invite-resource';
	const ROLE_GUEST = 'guest';
	const ROLE_USER = 'user';
	const ROLE_ADMIN = 'admin';
	const ROLE_SUPER = 'super';

	/**
	 *
	 * @var Acl
	 */
	protected $_acl;

	protected function _buildAcl()
	{
		$acl = new Acl();

		$config = $this->getServiceLocator()->get('config');
		if (!isset($config['acl_resource_map'])) {
			throw new \InvalidArgumentException('Please provide an ACL configuration with the key "acl_resource_map"');
		}

		foreach ($config['acl_resource_map'] as $roleName => $roleResources) {
			$acl->addRole(new \Zend\Permissions\Acl\Role\GenericRole($roleName), $roleResources['parents']);
			foreach ($roleResources['permissions'] as $resource => $privileges) {
				$this->_addAclResource($acl, $resource)
						->_addAclPermission($acl, $roleName, $resource, $privileges);
			}
			foreach ($roleResources['bans'] as $resource => $privileges) {
				$this->_addAclResource($acl, $resource)
						->_denyAclPermission($acl, $roleName, $resource, $privileges);
			}
		}
		return $acl;
	}

	/**
	 *
	 * @param \Zend\Permissions\Acl\Acl $acl
	 * @param type $resource
	 * @return \Application\Service\Permission
	 */
	protected function _addAclResource(Acl $acl, $resource)
	{
		if (!$acl->hasResource($resource)) {
			$acl->addResource(new GenericResource($resource));
		}
		return $this;
	}

	/**
	 *
	 * @param \Zend\Permissions\Acl\Acl $acl
	 * @param type $role
	 * @param type $resource
	 * @param type $privileges
	 */
	protected function _addAclPermission(Acl $acl, $role, $resource, $privileges)
	{
		$acl->allow($role, $resource, $privileges);
	}

	/**
	 *
	 * @param \Zend\Permissions\Acl\Acl $acl
	 * @param type $role
	 * @param type $resource
	 * @param type $privileges
	 */
	protected function _denyAclPermission(Acl $acl, $role, $resource, $privileges)
	{
		$acl->deny($role, $resource, $privileges);
	}

	/**
	 *
	 * @return Acl
	 */
	public function getAcl()
	{
		if (!isset($this->_acl)) {
			$this->_acl = $this->_buildAcl();
		}
		return $this->_acl;
	}

	public function getRoles()
	{
		return array(
			static::ROLE_USER => 'User',
			static::ROLE_ADMIN => 'Admin',
			static::ROLE_SUPER => 'Super User',
		);
	}

	public function allowed($resource, $privilege = null)
	{
		return $this->getAcl()->isAllowed($this->_currentUser()->getRole(), $resource, $privilege);
	}

	public function canInvite($role)
	{
		return $this->allowed(static::RESOURCE_INVITE, $role);
	}

	public function getAccessibleRoles()
	{
		$permittedRoles = array();
		foreach ($this->getRoles() as $role => $roleName) {
			if ($this->canInvite($role)) {
				$permittedRoles[] = array('role' => $role, 'name' => $roleName);
			}
		}
		return $permittedRoles;
	}

}

