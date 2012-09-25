<?php

/**
 * @file
 * Definition of Drupal\views\Tests\UI\DisplayTest.
 */

namespace Drupal\views\Tests\UI;

/**
 * Tests the handling of displays in the UI, adding removing etc.
 */
class DisplayTest extends UITestBase {

  public static function getInfo() {
    return array(
      'name' => 'Display tests',
      'description' => 'Tests the handling of displays in the UI, adding removing etc.',
      'group' => 'Views UI',
    );
  }

  /**
   * A helper method which creates a random view.
   */
  public function randomView(array $view = array()) {
    // Create a new view in the UI.
    $default = array();
    $default['human_name'] = $this->randomName(16);
    $default['name'] = strtolower($this->randomName(16));
    $default['description'] = $this->randomName(16);
    $default['page[create]'] = TRUE;
    $default['page[path]'] = $default['name'];

    $view += $default;

    $this->drupalPost('admin/structure/views/add', $view, t('Continue & edit'));

    return $default;
  }

  /**
   * Tests removing a display.
   */
  public function testRemoveDisplay() {
    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['name'] .'/edit';

    $this->drupalGet($path_prefix . '/default');
    $this->assertNoFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-delete', 'delete Page', 'Make sure there is no delete button on the default display.');

    $this->drupalGet($path_prefix . '/page');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-delete', 'delete Page', 'Make sure there is a delete button on the page display.');

    // Delete the page, so we can test the undo process.
    $this->drupalPost($path_prefix . '/page', array(), 'delete Page');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-undo-delete', 'undo delete of Page', 'Make sure there a undo button on the page display after deleting.');
    $this->assertTrue($this->xpath('//div[contains(@class, views-display-deleted-link)]'). 'Make sure the display link is marked as to be deleted.');

    // Undo the deleting of the display.
    $this->drupalPost($path_prefix . '/page', array(), 'undo delete of Page');
    $this->assertNoFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-undo-delete', 'undo delete of Page', 'Make sure there is no undo button on the page display after reverting.');
    $this->assertFieldById('edit-displays-settings-settings-content-tab-content-details-top-actions-delete', 'delete Page', 'Make sure there is a delete button on the page display after the reverting.');

    // Now delete again and save the view.
    $this->drupalPost($path_prefix . '/page', array(), 'delete Page');
    $this->drupalPost(NULL, array(), t('Save'));

    $this->assertNoLinkByHref($path_prefix . '/page', 'Make sure there is no display tab for the deleted display.');
  }

  /**
   * Tests adding a display.
   */
  public function testAddDisplay() {
    $settings['page[create]'] = FALSE;
    $view = $this->randomView($settings);

    $path_prefix = 'admin/structure/views/view/' . $view['name'] .'/edit';
    $this->drupalGet($path_prefix);

    // Add a new display.
    $this->drupalPost(NULL, array(), 'Add Page');
    // @todo Revising this after http://drupal.org/node/1793700 got in.
    $this->assertLinkByHref($path_prefix . '/page_1', 0, 'Make sure after adding a display the new display appears in the UI');
  }

  /**
   * Tests reordering of displays.
   */
  public function testReorderDisplay() {
    $view = array(
      'block[create]' => TRUE
    );
    $view = $this->randomView($view);
    $path_prefix = 'admin/structure/views/view/' . $view['name'] .'/edit';

    $edit = array();
    $this->drupalPost($path_prefix, $edit, t('Save'));
    $this->clickLink(t('reorder displays'));
    $this->assertTrue($this->xpath('//tr[@id="display-row-default"]'), 'Make sure the default display appears on the reorder listing');
    $this->assertTrue($this->xpath('//tr[@id="display-row-page"]'), 'Make sure the page display appears on the reorder listing');
    $this->assertTrue($this->xpath('//tr[@id="display-row-block"]'), 'Make sure the block display appears on the reorder listing');

    // Put the block display in front of the page display.
    $edit = array(
      'page[weight]' => 2,
      'block[weight]' => 1
    );
    $this->drupalPost(NULL, $edit, t('Apply'));
    $this->drupalPost(NULL, array(), t('Save'));

    $view = views_get_view($view['name']);
    $this->assertEqual($view->storage->display['default']['position'], 0, 'Make sure the master display comes first.');
    $this->assertEqual($view->storage->display['block']['position'], 1, 'Make sure the block display comes before the page display.');
    $this->assertEqual($view->storage->display['page']['position'], 2, 'Make sure the page display comes after the block display.');
  }

  /**
   * Tests that the correct display is loaded by default.
   */
  public function testDefaultDisplay() {
    $this->drupalGet('admin/structure/views/view/test_display');
    $elements = $this->xpath('//*[@id="views-page-display-title"]');
    $this->assertEqual(count($elements), 1, 'The page display is loaded as the default display.');
  }

  /**
   * Tests the cloning of a display.
   */
  public function testCloneDisplay() {
    $view = $this->randomView();
    $path_prefix = 'admin/structure/views/view/' . $view['name'] .'/edit';

    $this->drupalGet($path_prefix);
    $this->drupalPost(NULL, array(), 'clone Page');
    // @todo Revising this after http://drupal.org/node/1793700 got in.
    $this->assertLinkByHref($path_prefix . '/page_1', 0, 'Make sure after cloning the new display appears in the UI');
  }

}
