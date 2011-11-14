<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */
class CustomerDao extends BaseDao {

	/**
	 *
	 * @param type $limit
	 * @param type $offset
	 * @param type $sortField
	 * @param type $sortOrder
	 * @param type $activeOnly
	 * @return type 
	 */
	public function getCustomerList($limit=50, $offset=0, $sortField='name', $sortOrder='ASC', $activeOnly = 0) {

		$sortField = ($sortField == "") ? 'name' : $sortField;
		$sortOrder = ($sortOrder == "") ? 'ASC' : $sortOrder;
		$activeOnly = ($activeOnly == "") ? 'deleted' : $activeOnly;
		try {
			$q = Doctrine_Query :: create()
				->from('Customer')
				->where('deleted = ?', $activeOnly)
				->orderBy($sortField . ' ' . $sortOrder)
				->offset($offset)
				->limit($limit);
			return $q->execute();
		} catch (Exception $e) {
			throw new DaoException($e->getMessage());
		}
	}

	/**
	 *
	 * @param type $activeOnly
	 * @return type 
	 */
	public function getCustomerCount($activeOnly = 0) {

		$activeOnly = ($activeOnly == "") ? 'deleted' : $activeOnly;

		try {
			$q = Doctrine_Query :: create()
				->from('Customer')
				->where('deleted = ?', $activeOnly);
			$count = $q->execute()->count();
			return $count;
		} catch (Exception $e) {
			throw new DaoException($e->getMessage());
		}
	}

	/**
	 *
	 * @param type $customerId
	 * @return type 
	 */
	public function getCustomerById($customerId) {

		try {
			return Doctrine :: getTable('Customer')->find($customerId);
		} catch (Exception $e) {
			throw new DaoException($e->getMessage());
		}
	}

	/**
	 *
	 * @param type $customerId 
	 */
	public function deleteCustomer($customerId) {

		try {
			$customer = Doctrine :: getTable('Customer')->find($customerId);
			$customer->setDeleted(Customer::DELETED);
			$customer->save();
			$this->_deleteRelativeProjectsForCustomer($customerId);
		} catch (Exception $e) {
			throw new DaoException($e->getMessage());
		}
	}

	private function _deleteRelativeProjectsForCustomer($customerId) {

		try {
			$q = Doctrine_Query :: create()
				->from('Project')
				->where('deleted = ?', Project::ACTIVE_PROJECT)
				->andWhere('customer_id = ?', $customerId);
			$projects = $q->execute();

			foreach ($projects as $project) {
				$project->setDeleted(Project::DELETED_PROJECT);
				$project->save();
				$this->_deleteRelativeProjectActivitiesForProject($project->getProjectId());
				$this->_deleteRelativeProjectAdminsForProject($project->getProjectId());
			}
		} catch (Exception $e) {
			throw new DaoException($e->getMessage());
		}
	}

	private function _deleteRelativeProjectActivitiesForProject($projectId) {

		try {
			$q = Doctrine_Query :: create()
				->from('ProjectActivity')
				->where('deleted = ?', ProjectActivity::ACTIVE_PROJECT_ACTIVITY)
				->andWhere('project_id = ?', $projectId);
			$projectActivities = $q->execute();

			foreach ($projectActivities as $projectActivity) {
				$projectActivity->setDeleted(ProjectActivity::DELETED_PROJECT_ACTIVITY);
				$projectActivity->save();
			}
		} catch (Exception $e) {
			throw new DaoException($e->getMessage());
		}
	}

	private function _deleteRelativeProjectAdminsForProject($projectId) {

		try {
			$q = Doctrine_Query :: create()
				->delete('ProjectAdmin pa')
				->where('pa.project_id = ?', $projectId);
			$q->execute();
		} catch (Exception $e) {
			throw new DaoException($e->getMessage());
		}
	}

	/**
	 *
	 * @param type $activeOnly
	 * @return type 
	 */
	public function getAllCustomers($activeOnly = 0) {

		$activeOnly = ($activeOnly == "") ? 'deleted' : $activeOnly;

		try {
			$q = Doctrine_Query :: create()
				->from('Customer')
				->where('deleted =?', $activeOnly);
			return $q->execute();
		} catch (Exception $e) {
			throw new DaoException($e->getMessage());
		}
	}

	/**
	 *
	 * @param type $customerId
	 * @return type 
	 */
	public function hasCustomerGotTimesheetItems($customerId) {

		try {
			$q = Doctrine_Query :: create()
				->select("COUNT(*)")
				->from('TimesheetItem ti')
				->leftJoin('ti.Project p')
				->leftJoin('p.Customer c')
				->where('c.customerId = ?', $customerId);
			$count = $q->fetchOne(array(), Doctrine_Core::HYDRATE_SINGLE_SCALAR);
			return ($count > 0);
		} catch (Exception $e) {
			throw new DaoException($e->getMessage());
		}
	}

}

?>