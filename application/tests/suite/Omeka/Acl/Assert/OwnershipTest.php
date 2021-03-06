<?php
/**
 * @copyright Roy Rosenzweig Center for History and New Media, 2011
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Omeka
 * @subpackage Tests
 */

/**
 * @package Omeka
 * @subpackage Tests
 */
class Omeka_Acl_Assert_OwnershipTest extends Omeka_Test_AppTestCase
{
    private $_users;
    private $_items;

    public function setUpLegacy()
    {
        parent::setUpLegacy();

        $super = new User;
        $super->role = 'super';
        $super->id = 1;
        $contributor = new User;
        $contributor->role = 'contributor';
        $contributor->id = 2;
        $researcher = new User;
        $researcher->role = 'researcher';
        $researcher->id = 3;

        $this->_users = array(
            'super' => $super,
            'contributor' => $contributor,
            'researcher' => $researcher
        );
    }

    public function tearDownLegacy()
    {
        release_object($this->_users);
        parent::tearDownLegacy();
    }

    /**
     * Test the ownership ACL for a specific user.
     *
     * @dataProvider userResourceProvider
     * @param string $userKey
     * @param bool $whenOwner
     * @param bool $whenNotOwner
     * @param bool $generally
     */
    public function testOwnershipAcl($userKey, $whenOwner, $whenNotOwner, $generally)
    {
        $user = $this->_users[$userKey];

        $items = array(
            'addedBySelf' => $this->_getMockItem(true),
            'notAddedBySelf' => $this->_getMockItem(false),
        );

        foreach ($items as $itemKey => $item) {
            if ($itemKey == 'addedBySelf') {
                $expectation = $whenOwner;
            } else {
                $expectation = $whenNotOwner;
            }

            $assertionType = $expectation ? 'can' : 'cannot';

            $this->assertEquals($expectation,
                $this->acl->isAllowed($user, $item, 'edit'),
                "Failed asserting that $userKey $assertionType edit item $itemKey");
            $this->assertEquals($expectation,
                $this->acl->isAllowed($user, $item, 'delete'),
                "Failed asserting that $userKey $assertionType delete item $itemKey.");
        }
        $this->assertEquals($generally, $this->acl->isAllowed($user, 'Items', 'edit'));
        $this->assertEquals($generally, $this->acl->isAllowed($user, 'Items', 'delete'));
    }

    public function userResourceProvider()
    {
        return array(
            // $userKey, $whenOwner, $whenNotOwner, $generally
            array('super', true, true, true),
            array('contributor', true, false, true),
            array('researcher', false, false, false)
        );
    }

    /**
     * Get a mocked Item object with stubs for the methods needed by the
     * ACL.
     *
     * Avoids having to save new items or attempt to create the
     * correct entity relation structure.
     *
     * @param bool $addedBySelf
     * @return Item mock Item object
     */
    private function _getMockItem($addedBySelf)
    {
        $item = $this->getMockBuilder('Item')->setMethods(array('getResourceId', 'isOwnedBy'))->getMock();
        $item->expects($this->any())
             ->method('getResourceId')
             ->will($this->returnValue('Items'));
        $item->expects($this->any())
             ->method('isOwnedBy')
             ->will($this->returnValue($addedBySelf));
        return $item;
    }
}
