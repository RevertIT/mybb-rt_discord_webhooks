<?php
/**
 * RT Discord Webhooks
 *
 * A simple integration of discord webhooks with multiple insertions
 *
 * @package rt_discord_webhooks
 * @author  RevertIT <https://github.com/revertit>
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

declare(strict_types=1);

namespace rt\DiscordWebhooks\Discord;

use Form;
use FormContainer;
use MyBB;
use rt\DiscordWebhooks\Core;
use Page;

final class AdminWebhooksAdd extends AdminWebhooksConfig
{
    public function load(array $sub_tabs, Page $page): void
    {
        $page->output_header($this->lang->{$this->prefix . '_menu'} . ' - ' . $this->lang->{$this->prefix .'_tab_' . 'add_webhook'});
        $page->output_nav_tabs($sub_tabs, 'add_webhook');

        if ($this->mybb->request_method === 'post')
        {
            if ($this->totalWebhookRows() > 100)
            {
                flash_message($this->lang->rt_discord_webhooks_webhooks_more_than_100, 'error');
                admin_redirect("index.php?module=tools-{$this->prefix}&amp;action=add_webhook");
            }
            // Validate the webhook URL
            if (!preg_match('/^https:\/\/discord\.com\/api\/webhooks\/\d+\/[\w-]+$/i', $this->mybb->get_input('webhook_url')))
            {
                flash_message($this->lang->rt_discord_webhooks_webhooks_url_invalid, 'error');
                admin_redirect("index.php?module=tools-{$this->prefix}&amp;action=add_webhook");
            }
            if ($this->duplicateWebhookUrl($this->mybb->get_input('webhook_url')))
            {
                flash_message($this->lang->rt_discord_webhooks_webhooks_url_duplicate, 'error');
                admin_redirect("index.php?module=tools-{$this->prefix}&amp;action=add_webhook");
            }
            if (empty($this->mybb->get_input('bot_id', MyBB::INPUT_INT)))
            {
                flash_message($this->lang->rt_discord_webhooks_webhooks_bot_id_invalid, 'error');
                admin_redirect("index.php?module=tools-{$this->prefix}&amp;action=add_webhook");
            }
            if (!get_user($this->mybb->get_input('bot_id', MyBB::INPUT_INT)))
            {
                flash_message($this->lang->rt_discord_webhooks_webhooks_bot_id_not_found, 'error');
                admin_redirect("index.php?module=tools-{$this->prefix}&amp;action=add_webhook");
            }
            if (!empty($this->mybb->get_input('webhook_embeds_color')) && !DiscordHelper::isValidHexColor($this->mybb->get_input('webhook_embeds_color')))
            {
                flash_message($this->lang->rt_discord_webhooks_webhook_embeds_color_invalid, 'error');
                admin_redirect("index.php?module=tools-{$this->prefix}&amp;action=add_webhook");
            }
            if ($this->mybb->get_input('character_limit', MyBB::INPUT_INT) > 2000)
            {
                flash_message($this->lang->rt_discord_webhooks_webhooks_char_limit_invalid, 'error');
                admin_redirect("index.php?module=tools-{$this->prefix}&amp;action=add_webhook");
            }
            if (!empty($this->mybb->get_input('webhook_name')) && strlen($this->mybb->get_input('webhook_name')) > 255)
            {
                flash_message($this->lang->rt_discord_webhooks_webhooks_name_limit, 'error');
                admin_redirect("index.php?module=tools-{$this->prefix}&amp;action=add_webhook");
            }

            $watch_forums = 0;
            if (!empty($this->mybb->get_input('watch_forums', MyBB::INPUT_ARRAY)))
            {
                $watch_forums =  implode(',', array_map('intval', $this->mybb->get_input('watch_forums', MyBB::INPUT_ARRAY)));
            }

            $watch_usergroups = 0;
            if (!empty($this->mybb->get_input('watch_usergroups', MyBB::INPUT_ARRAY)))
            {
                $watch_usergroups =  implode(',', array_map('intval', $this->mybb->get_input('watch_usergroups', MyBB::INPUT_ARRAY)));
            }

            $insert_data = [
                'webhook_url' => $this->db->escape_string($this->mybb->get_input('webhook_url')),
                'bot_id' => $this->mybb->get_input('bot_id', MyBB::INPUT_INT),
                'webhook_embeds' => !empty($this->mybb->get_input('webhook_embeds', MyBB::INPUT_INT)) ? 1 : 0,
                'watch_new_threads' => !empty($this->mybb->get_input('watch_new_threads', MyBB::INPUT_INT)) ? 1 : 0,
                'watch_new_posts' => !empty($this->mybb->get_input('watch_new_posts', MyBB::INPUT_INT)) ? 1 : 0,
                'watch_edit_threads' => !empty($this->mybb->get_input('watch_edit_threads', MyBB::INPUT_INT)) ? 1 : 0,
                'watch_edit_posts' => !empty($this->mybb->get_input('watch_edit_posts', MyBB::INPUT_INT)) ? 1 : 0,
                'watch_delete_threads' => !empty($this->mybb->get_input('watch_delete_threads', MyBB::INPUT_INT)) ? 1 : 0,
                'watch_delete_posts' => !empty($this->mybb->get_input('watch_delete_posts', MyBB::INPUT_INT)) ? 1 : 0,
                'watch_new_registrations' => !empty($this->mybb->get_input('watch_new_registrations', MyBB::INPUT_INT)) ? 1 : 0,
                'allowed_mentions' => !empty($this->mybb->get_input('allowed_mentions', MyBB::INPUT_INT)) ? 1 : 0,
                'watch_usergroups' => $this->db->escape_string($watch_usergroups),
                'watch_forums' => $this->db->escape_string($watch_forums)
            ];

            if (!empty($this->mybb->get_input('webhook_embeds_color')))
            {
                $insert_data['webhook_embeds_color'] = $this->db->escape_string($this->mybb->get_input('webhook_embeds_color'));
            }

            if (!empty($this->mybb->get_input('webhook_embeds_thumbnail')))
            {
                $insert_data['webhook_embeds_thumbnail'] = $this->db->escape_string($this->mybb->get_input('webhook_embeds_thumbnail'));
            }

            if (!empty($this->mybb->get_input('webhook_embeds_footer_text')))
            {
                $insert_data['webhook_embeds_footer_text'] = $this->db->escape_string($this->mybb->get_input('webhook_embeds_footer_text'));
            }

            if (!empty($this->mybb->get_input('webhook_embeds_footer_icon_url')))
            {
                $insert_data['webhook_embeds_footer_icon_url'] = $this->db->escape_string($this->mybb->get_input('webhook_embeds_footer_icon_url'));
            }

            if (!empty($this->mybb->get_input('character_limit')))
            {
                $insert_data['character_limit'] = $this->db->escape_string($this->mybb->get_input('character_limit', MyBB::INPUT_INT));
            }

            if (!empty($this->mybb->get_input('webhook_name')))
            {
                $insert_data['webhook_name'] = $this->db->escape_string($this->mybb->get_input('webhook_name'));
            }

            $this->db->insert_query(Core::get_plugin_info('prefix'), $insert_data);

            // Rebuild Webhooks cache
            $this->rebuildWebhooks();

            flash_message($this->lang->rt_discord_webhooks_webhooks_added, 'success');
            admin_redirect("index.php?module=tools-{$this->prefix}");
        }

        $form = new Form("index.php?module=tools-{$this->prefix}&amp;action=add_webhook", "post", "add_webhook");
        $form_container = new FormContainer($this->lang->rt_discord_webhooks_tab_add_webhook);
        $form_container->output_row($this->lang->rt_discord_webhooks_webhooks_url." <em>*</em>", "", $form->generate_text_box('webhook_url', $this->mybb->get_input('webhook_url'), array('id' => 'webhook_url')), 'webhook_url');
        $form_container->output_row($this->lang->rt_discord_webhooks_webhooks_name, $this->lang->rt_discord_webhooks_webhooks_name_desc, $form->generate_text_box('webhook_name', $this->mybb->get_input('webhook_name'), array('id' => 'webhook_name')), 'webhook_name');
        $form_container->output_row($this->lang->rt_discord_webhooks_webhook_embeds." <em>*</em>", $this->lang->rt_discord_webhooks_webhook_embeds_desc, $form->generate_on_off_radio('webhook_embeds', $this->mybb->get_input('webhook_embeds', MyBB::INPUT_INT), true, array('id' => 'webhook_embeds_on', 'class' => 'webhook_embeds'), array('id' => 'webhook_embeds_off', 'class' => 'webhook_embeds')), 'webhook_embeds');
        $form_container->output_row($this->lang->rt_discord_webhooks_webhooks_allowed_mentions." <em>*</em>", $this->lang->rt_discord_webhooks_webhooks_allowed_mentions_desc, $form->generate_yes_no_radio('allowed_mentions', $this->mybb->get_input('allowed_mentions', MyBB::INPUT_INT), true, array('id' => 'allowed_mentions_yes'), array('id' => 'allowed_mentions_no')), 'allowed_mentions', ['class' => 'allowed_mentions']);
        $form_container->output_row($this->lang->rt_discord_webhooks_webhook_embeds_color, "", $form->generate_text_box('webhook_embeds_color', $this->mybb->get_input('webhook_embeds_color'), array('id' => 'webhook_embeds_color')), 'webhook_embeds_color', ['class' => 'webhook_embeds_color']);
        $form_container->output_row($this->lang->rt_discord_webhooks_webhook_embeds_thumbnail, "", $form->generate_text_box('webhook_embeds_thumbnail', $this->mybb->get_input('webhook_embeds_thumbnail'), array('id' => 'webhook_embeds_thumbnail')), 'webhook_embeds_thumbnail', ['class' => 'webhook_embeds_thumbnail']);
        $form_container->output_row($this->lang->rt_discord_webhooks_webhook_embeds_footer_text, "", $form->generate_text_box('webhook_embeds_footer_text', $this->mybb->get_input('webhook_embeds_footer_text'), array('id' => 'webhook_embeds_footer_text')), 'webhook_embeds_footer_text', ['class' => 'webhook_embeds_footer_text']);
        $form_container->output_row($this->lang->rt_discord_webhooks_webhook_embeds_footer_icon_url, "", $form->generate_text_box('webhook_embeds_footer_icon_url', $this->mybb->get_input('webhook_embeds_footer_icon_url'), array('id' => 'webhook_embeds_footer_icon_url')), 'webhook_embeds_footer_icon_url', ['class' => 'webhook_embeds_footer_icon_url']);
        $form_container->output_row($this->lang->rt_discord_webhooks_webhooks_char_limit." <em>*</em>", $this->lang->rt_discord_webhooks_webhooks_char_limit_desc, $form->generate_numeric_field('character_limit', $this->mybb->get_input('character_limit', MyBB::INPUT_INT), array('id' => 'character_limit', 'min' => 1, 'max' => 2000)), 'character_limit');
        $form_container->output_row($this->lang->rt_discord_webhooks_webhooks_bot_id." <em>*</em>", "", $form->generate_numeric_field('bot_id', $this->mybb->get_input('bot_id', MyBB::INPUT_INT), array('id' => 'bot_id')), 'bot_id');
        $form_container->output_row($this->lang->rt_discord_webhooks_webhooks_watch_new_threads." <em>*</em>", $this->lang->rt_discord_webhooks_webhooks_watch_new_threads_desc, $form->generate_on_off_radio('watch_new_threads', $this->mybb->get_input('watch_new_threads', MyBB::INPUT_INT), true, array('id' => 'watch_new_threads_on'), array('id' => 'watch_new_threads_off')), 'watch_new_threads');
        $form_container->output_row($this->lang->rt_discord_webhooks_webhooks_watch_new_posts." <em>*</em>", $this->lang->rt_discord_webhooks_webhooks_watch_new_posts_desc, $form->generate_on_off_radio('watch_new_posts', $this->mybb->get_input('watch_new_posts', MyBB::INPUT_INT), true, array('id' => 'watch_new_posts_on'), array('id' => 'watch_new_posts_off')), 'watch_new_posts');
        $form_container->output_row($this->lang->rt_discord_webhooks_webhooks_watch_edit_threads." <em>*</em>", $this->lang->rt_discord_webhooks_webhooks_watch_edit_threads_desc, $form->generate_on_off_radio('watch_edit_threads', $this->mybb->get_input('watch_edit_threads', MyBB::INPUT_INT), true, array('id' => 'watch_edit_threads_on'), array('id' => 'watch_edit_threads_off')), 'watch_edit_threads');
        $form_container->output_row($this->lang->rt_discord_webhooks_webhooks_watch_edit_posts." <em>*</em>", $this->lang->rt_discord_webhooks_webhooks_watch_edit_posts_desc, $form->generate_on_off_radio('watch_edit_posts', $this->mybb->get_input('watch_edit_posts', MyBB::INPUT_INT), true, array('id' => 'watch_edit_posts_on'), array('id' => 'watch_edit_posts_off')), 'watch_edit_posts_posts');
        $form_container->output_row($this->lang->rt_discord_webhooks_webhooks_watch_delete_threads." <em>*</em>", $this->lang->rt_discord_webhooks_webhooks_watch_delete_threads_desc, $form->generate_on_off_radio('watch_delete_threads', $this->mybb->get_input('watch_delete_threads', MyBB::INPUT_INT), true, array('id' => 'watch_delete_threads_on'), array('id' => 'watch_delete_threads_off')), 'watch_delete_threads');
        $form_container->output_row($this->lang->rt_discord_webhooks_webhooks_watch_delete_posts." <em>*</em>", $this->lang->rt_discord_webhooks_webhooks_watch_delete_posts_desc, $form->generate_on_off_radio('watch_delete_posts', $this->mybb->get_input('watch_delete_posts', MyBB::INPUT_INT), true, array('id' => 'watch_delete_posts_on'), array('id' => 'watch_delete_posts_off')), 'watch_delete_posts');
        $form_container->output_row($this->lang->rt_discord_webhooks_webhooks_watch_new_registrations." <em>*</em>", $this->lang->rt_discord_webhooks_webhooks_watch_new_registrations_desc, $form->generate_on_off_radio('watch_new_registrations', $this->mybb->get_input('watch_new_registrations', MyBB::INPUT_INT), true, array('id' => 'watch_new_registrations_on'), array('id' => 'watch_new_registrations_off')), 'watch_new_registrations');

        $selected_values = [];
        if (!empty($this->mybb->get_input('watch_usergroups', MyBB::INPUT_ARRAY)))
        {
            foreach ($this->mybb->get_input('watch_usergroups', MyBB::INPUT_ARRAY) as $value)
            {
                $selected_values[] = (int) $value;
            }
        }
        $form_container->output_row($this->lang->rt_discord_webhooks_webhooks_watch_usergroups." <em>*</em>", $this->lang->rt_discord_webhooks_webhooks_watch_usergroups_desc, $form->generate_group_select('watch_usergroups[]', $selected_values, array('multiple' => true, 'size' => 5)), 'watch_usergroups');

        $selected_values = [];
        if (!empty($this->mybb->get_input('watch_forums', MyBB::INPUT_ARRAY)))
        {
            foreach ($this->mybb->get_input('watch_forums', MyBB::INPUT_ARRAY) as $value)
            {
                $selected_values[] = (int) $value;
            }
        }
        $form_container->output_row($this->lang->rt_discord_webhooks_webhooks_watch_forums." <em>*</em>", $this->lang->rt_discord_webhooks_webhooks_watch_forums_desc, $form->generate_forum_select('watch_forums[]', $selected_values, array('multiple' => true, 'size' => 5, 'main_option' => $this->lang->all_forums)), 'watch_forums');
        $form_container->end();

        $buttons[] = $form->generate_submit_button($this->lang->rt_discord_webhooks_webhooks_submit);

        $form->output_submit_wrapper($buttons);
        $form->end();

        // Add Peekers
        echo <<<PEEKERS
        <script type="text/javascript" src="./jscripts/peeker.js?ver=1821"></script>
        <script type="text/javascript">
            $(function()
            {
                new Peeker($(".webhook_embeds"), $(".allowed_mentions, .webhook_embeds_footer_text, .webhook_embeds_footer_icon_url, .webhook_embeds_color, .webhook_embeds_thumbnail"), 1, true);
            });
        </script>
        PEEKERS;
    }

}