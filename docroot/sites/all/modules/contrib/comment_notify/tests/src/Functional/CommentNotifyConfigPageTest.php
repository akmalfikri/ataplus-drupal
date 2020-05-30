<?php

namespace Drupal\Tests\comment_notify\Functional;

use Drupal\comment\CommentInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;

/**
 * Tests for the comment_notify module.
 *
 * @group comment_notify
 */
class CommentNotifyConfigPageTest extends CommentNotifyTestBase {

  /**
   * Test to all the options are saved correctly.
   */
  public function testConfigPage() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet("admin/config/people/comment_notify");

    // Test the default values are working.
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');

    // Test that the content types are saved correctly.
    $this->getSession()->getPage()->checkField('bundle_types[node--article--comment]');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');
    $this->drupalGet("admin/config/people/comment_notify");
    $this->assertTrue($this->getSession()->getPage()->hasCheckedField('bundle_types[node--article--comment]'));

    // Test that Available subscription modes are saved correctly.
    $this->getSession()->getPage()->checkField('available_alerts[1]');
    $this->getSession()->getPage()->checkField('available_alerts[2]');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');
    $this->drupalGet("admin/config/people/comment_notify");
    $this->assertTrue($this->getSession()->getPage()->hasCheckedField('available_alerts[1]'));
    $this->assertTrue($this->getSession()->getPage()->hasCheckedField('available_alerts[2]'));

    // Test that at least one subscription mode must be enabled.
    $this->getSession()->getPage()->uncheckField('available_alerts[1]');
    $this->getSession()->getPage()->uncheckField('available_alerts[2]');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->responseContains('You must enable at least one subscription mode.');

    $this->getSession()->getPage()->uncheckField('available_alerts[1]');
    $this->getSession()->getPage()->checkField('available_alerts[2]');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');
    $this->drupalGet("admin/config/people/comment_notify");
    $this->assertTrue($this->getSession()->getPage()->hasUncheckedField('available_alerts[1]'));
    $this->assertTrue($this->getSession()->getPage()->hascheckedField('available_alerts[2]'));
    // The default state select must hide the option as well.
    $field = $this->getSession()->getPage()->findField('Default state for the notification selection box');
    $this->assertFalse(strpos($field->getHtml(), 'All Comments'));
    $this->assertTrue(strpos($field->getHtml(), 'Replies to my comment'));

    $this->getSession()->getPage()->uncheckField('available_alerts[2]');
    $this->getSession()->getPage()->checkField('available_alerts[1]');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');
    $this->drupalGet("admin/config/people/comment_notify");
    $this->assertTrue($this->getSession()->getPage()->hasUncheckedField('available_alerts[2]'));
    $this->assertTrue($this->getSession()->getPage()->hascheckedField('available_alerts[1]'));
    // The default state select must hide the option as well.
    $field = $this->getSession()->getPage()->findField('Default state for the notification selection box');
    $this->assertTrue(strpos($field->getHtml(), 'All comments'));
    $this->assertFalse(strpos($field->getHtml(), 'Replies to my comment'));

    $this->getSession()->getPage()->checkField('available_alerts[1]');
    $this->getSession()->getPage()->checkField('available_alerts[2]');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');
    $this->drupalGet("admin/config/people/comment_notify");
    $this->assertTrue($this->getSession()->getPage()->hascheckedField('available_alerts[1]'));
    $this->assertTrue($this->getSession()->getPage()->hascheckedField('available_alerts[2]'));
    $field = $this->getSession()->getPage()->findField('Default state for the notification selection box');
    $this->assertTrue(strpos($field->getHtml(), 'All comments'));
    $this->assertTrue(strpos($field->getHtml(), 'Replies to my comment'));

    $this->getSession()->getPage()->selectFieldOption('Default state for the notification selection box', "0");
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');
    $this->drupalGet("admin/config/people/comment_notify");
    $field = $this->getSession()->getPage()->findField('Default state for the notification selection box');
    $this->assertTrue($field->getValue() == "0");

    $this->drupalGet("admin/config/people/comment_notify");
    $this->getSession()->getPage()->selectFieldOption('Default state for the notification selection box', "1");
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');
    $this->drupalGet("admin/config/people/comment_notify");
    $field = $this->getSession()->getPage()->findField('Default state for the notification selection box');
    $this->assertTrue($field->getValue() == "1");

    $this->getSession()->getPage()->selectFieldOption('Default state for the notification selection box', "2");
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');
    $this->drupalGet("admin/config/people/comment_notify");
    $field = $this->getSession()->getPage()->findField('Default state for the notification selection box');
    $this->assertTrue($field->getValue() == "2");

    $this->getSession()->getPage()->checkField('Subscribe users to their entity follow-up notification emails by default');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');
    $this->drupalGet("admin/config/people/comment_notify");
    $this->assertTrue($this->getSession()->getPage()->hasCheckedField('Subscribe users to their entity follow-up notification emails by default'));

    $this->getSession()->getPage()->uncheckField('Subscribe users to their entity follow-up notification emails by default');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');
    $this->drupalGet("admin/config/people/comment_notify");
    $this->assertTrue($this->getSession()->getPage()->hasUncheckedField('Subscribe users to their entity follow-up notification emails by default'));

    $this->getSession()->getPage()->fillField('Default mail text for sending out notifications to commenters', 'Hello');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');
    $this->drupalGet("admin/config/people/comment_notify");
    $field = $this->getSession()->getPage()->findField('Default mail text for sending out notifications to commenters');
    $this->assertTrue($field->getValue() == 'Hello');

    $this->getSession()->getPage()->fillField('Default mail text for sending out the notifications to entity authors', 'Hello');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->responseContains('The configuration options have been saved.');
    $this->drupalGet("admin/config/people/comment_notify");
    $field = $this->getSession()->getPage()->findField('Default mail text for sending out the notifications to entity authors');
    $this->assertTrue($field->getValue() == 'Hello');
    $this->drupalLogout();

  }

  /**
   * Tests the warning message when anonymous users have configuration problems.
   */
  public function testsAnonymousProblemsAreReported() {

    // Tests that the anonymous users have the permission to use comment notify
    // but aren't allowed to leave posts.
    user_role_grant_permissions(
      AccountInterface::ANONYMOUS_ROLE,
      [
        'access comments',
        'access content',
        'subscribe to comments',
      ]
    );

    $this->drupalLogin($this->adminUser);
    $this->drupalGet("admin/config/people/comment_notify");

    // Test that a warning error is displayed when anonymous users have the
    // permission to use comment notify but cannot post posts.
    $this->assertSession()->responseContains('Anonymous commenters have the permission to subscribe to comments but they need to be allowed to:');
    $this->assertSession()->responseContains('Post comments');

    user_role_grant_permissions(
      AccountInterface::ANONYMOUS_ROLE,
      [
        'post comments',
      ]
    );
    $this->drupalGet("admin/config/people/comment_notify");
    $this->assertSession()->responseNotContains('Anonymous commenters have the permission to subscribe to comments but they need to be allowed to:');
    $this->assertSession()->responseNotContains('Post comments');

    // Tests that a warning error is displayed when anonymous users haven't
    // permission to leave their contact information.
    $comment_field = FieldConfig::loadByName('node', 'article', 'comment');
    $comment_field->setSetting('anonymous', CommentInterface::ANONYMOUS_MAYNOT_CONTACT);
    $comment_field->save();
    $this->drupalGet("admin/config/people/comment_notify");
    $this->assertSession()->responseContains('Anonymous commenters have the permission to subscribe to comments but they need to be allowed to:');
    $this->assertSession()->responseContains('Leave their contact information on the following fields:');
    $this->assertSession()->responseContains('node--article--comment');

    // If the field_ui module is installed then the field with the problem must
    // be a link.
    $this->container->get('module_installer')->install(['field_ui'], TRUE);
    $this->rebuildContainer();
    $this->drupalGet("admin/config/people/comment_notify");
    $this->assertSession()->responseContains('Anonymous commenters have the permission to subscribe to comments but they need to be allowed to:');
    $this->assertSession()->responseContains('Leave their contact information on the following fields:');
    $this->assertSession()->responseContains('node--article--comment');
    $this->assertSession()->linkByHrefExists('/admin/structure/types/manage/article/fields/node.article.comment');
  }

}
