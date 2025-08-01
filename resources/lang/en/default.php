<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation' => [
        'message_resource' => [
            'label' => 'Messages',
            'group' => 'Services',
        ],
        'message_type_resource' => [
            'label' => 'Message types',
            'group' => 'Settings',
        ],
    ],

    // Model labels
    'models' => [
        'message' => [
            'label' => 'Message',
            'plural_label' => 'Messages',
        ],
        'message_type' => [
            'label' => 'Message Type',
            'plural_label' => 'Message Types',
        ],
    ],

    // Forms
    'forms' => [
        'message' => [
            'sections' => [
                'content' => 'Message Content',
                'basic_info' => 'Basic Information',
                'approval_settings' => 'Approval Settings',
                'advanced_settings' => 'Advanced Settings',
            ],
            'fields' => [
                'recipient_id' => [
                    'label' => 'Recipient',
                ],
                'subject' => [
                    'label' => 'Subject',
                ],
                'content' => [
                    'label' => 'Content',
                ],
                'attachments' => [
                    'label' => 'Attachments',
                ],
                'message_type_id' => [
                    'label' => 'Message Type',
                ],
                'priority' => [
                    'label' => 'Priority',
                ],
                'save_as_draft' => [
                    'label' => 'Save as Draft',
                    'helper_text' => 'Enable to save as draft, disable to send immediately',
                ],
            ],
        ],
        'message_type' => [
            'fields' => [
                'name' => [
                    'label' => 'Name',
                ],
                'description' => [
                    'label' => 'Description',
                ],
                'requires_approval' => [
                    'label' => 'Requires Approval',
                ],
                'approver_role_id' => [
                    'label' => 'Approver Role',
                ],
                'custom_fields' => [
                    'label' => 'Custom Fields',
                    'key_label' => 'Field',
                    'value_label' => 'Default Value',
                    'add_action_label' => 'Add Field',
                ],
                'is_active' => [
                    'label' => 'Active',
                ],
                'sort_order' => [
                    'label' => 'Display Order',
                ],
            ],
        ],
        'approval' => [
            'reason' => [
                'label' => 'Reason (optional)',
            ],
        ],
        'rejection' => [
            'reason' => [
                'label' => 'Rejection Reason',
            ],
        ],
        'transfer' => [
            'new_recipient_id' => [
                'label' => 'New Recipient',
            ],
            'reason' => [
                'label' => 'Transfer Reason',
            ],
        ],
        'reply' => [
            'subject' => [
                'label' => 'Subject',
                'prefix' => 'Re: ',
            ],
            'content' => [
                'label' => 'Reply Content',
            ],
        ],
    ],

    // Tables
    'tables' => [
        'columns' => [
            'subject' => 'Subject',
            'sender' => 'Sender',
            'recipient' => 'Recipient',
            'code' => 'Code',
            'type' => 'Type',
            'priority' => 'Priority',
            'status' => 'Status',
            'created_at' => 'Created at',
            'sent_at' => 'Sent at',
            'read_at' => 'Read at',
            'replies_count' => 'Replies',
            'is_active' => 'Active',
            'name' => 'Name',
            'approver_role' => 'Approver Role',
            'requires_approval' => 'Approval',
            'messages_count' => 'Messages',
            'sort_order' => 'Order',
        ],
        'placeholders' => [
            'not_read' => 'Not read',
            'no_approver' => '—',
        ],
        'filters' => [
            'status' => [
                'label' => 'Status',
            ],
            'priority' => [
                'label' => 'Priority',
            ],
            'message_type' => [
                'label' => 'Type',
            ],
            'unread' => [
                'label' => 'Unread',
            ],
            'urgent' => [
                'label' => 'Urgent',
            ],
            'with_replies' => [
                'label' => 'With Replies',
            ],
            'transferable' => [
                'label' => 'Transferable',
            ],
            'active_status' => [
                'label' => 'Status',
                'placeholder' => 'All',
                'true_label' => 'Active',
                'false_label' => 'Inactive',
            ],
            'approval_status' => [
                'label' => 'Approval',
                'placeholder' => 'All',
                'true_label' => 'Requires Approval',
                'false_label' => 'No Approval Required',
            ],
        ],
    ],

    // Actions
    'actions' => [
        'create_message' => 'New Message',
        'send_message' => 'Send Message',
        'approve' => [
            'label' => 'Approve',
            'modal_heading' => 'Approve Message',
            'modal_description' => 'Are you sure you want to approve this message?',
        ],
        'reject' => [
            'label' => 'Reject',
            'modal_heading' => 'Reject Message',
        ],
        'transfer' => [
            'label' => 'Transfer',
            'modal_heading' => 'Transfer Message',
        ],
        'reply' => [
            'label' => 'Reply',
            'modal_heading' => 'Reply to Message',
        ],
        'mark_read' => [
            'label' => 'Mark as Read',
        ],
    ],

    // Tabs
    'tabs' => [
        'all' => 'All',
        'received' => 'Received',
        'sent' => 'Sent',
        'unread' => 'Unread',
        'pending_approval' => 'Pending Approval',
        'drafts' => 'Drafts',
    ],

    // Enums
    'enums' => [
        'priority' => [
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
        ],
        'status' => [
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'sent' => 'Sent',
            'read' => 'Read',
            'archived' => 'Archived',
            'new_message' => 'New Message',
        ],
    ],

    // System messages
    'messages' => [
        'success' => [
            'message_approved' => 'Message approved successfully!',
            'message_rejected' => 'Message rejected successfully!',
            'message_transferred' => 'Message transferred successfully!',
            'reply_sent' => 'Reply sent successfully!',
        ],
        'error' => [
            'approve_message' => 'Error approving message',
            'reject_message' => 'Error rejecting message',
            'transfer_message' => 'Error transferring message',
            'send_reply' => 'Error sending reply',
            'cannot_send_to_self' => 'You cannot send a message to yourself.',
            'cannot_transfer_with_replies' => 'Cannot transfer messages that have replies.',
            'no_permission_to_view' => 'You do not have permission to view this message.',
        ],
    ],

    // Blade templates
    'view' => [
        'thread' => [
            'created_at' => 'Created at:',
            'replied_at' => 'Replied at:',
            'subject_prefix' => 'Subject:',
            'read_at' => 'Read at',
            'not_read' => 'Not read',
            'pending_approval' => 'Pending approval',
            'message_info' => 'Message Information',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'pending' => 'Pending',
            'transfers_count' => 'transfer(s)',
            'created_by' => 'Created by',
            'recipient' => 'Recipient:',
            'approved_by' => 'Approved by',
            'rejected_by' => 'Rejected by',
            'awaiting_approval' => 'Awaiting approval',
            'transferred_from_to' => 'Transferred from',
            'to' => 'to',
            'reason' => 'Reason:',
            'current_responsible' => 'Current responsible:',
            'current' => 'Current',
            'replies_in_thread' => 'reply(ies) in this thread',
            'current_responsible_footer' => 'Current responsible:',
            'created_at_footer' => 'Created at',
        ],
    ],

    // Validations
    'validation' => [
        'message_type_name_required' => 'Message type name is required.',
        'only_recipient_can_mark_read' => 'Only the recipient can mark the message as read.',
        'cannot_send_to_self' => 'You cannot send a message to yourself.',
    ],

    'logs' => [
        'message_created_without_type' => 'Message created without defined type',
        'message_marked_as_read' => 'Message marked as read',
        'message_converted_to_approval' => 'Message converted to require approval',
    ],
    // Notificações
    'notifications' => [
        'mail' => [
            'greeting' => 'Hello :name!',
            'footer' => 'Thank you for using our message system.',
        ],
        'actions' => [
            'view_message' => 'View Message',
            'view_reply' => 'View Reply',
            'mark_as_read' => 'Mark as Read',
            'dismiss' => 'Dismiss',
        ],
        'new_message' => [
            'title' => 'New Message',
            'body' => 'You received a new message from :sender_name: ":subject"',
        ],
        'reply' => [
            'title' => 'New Reply',
            'body' => ':sender_name replied to the message: ":subject"',
        ],
        'pending_approval' => [
            'title' => 'Message Pending Approval',
            'body' => ':sender_name sent a message that needs approval: ":subject"',
        ],
        'approved' => [
            'title' => 'Message Approved',
            'body' => 'Your message ":subject" has been approved and sent.',
        ],
        'rejected' => [
            'title' => 'Message Rejected',
            'body' => 'Your message ":subject" has been rejected.',
            'reason' => 'Reason: :reason',
        ],
        'transferred' => [
            'title' => 'Message Transferred',
            'body' => 'Your message ":subject" has been transferred to you.',
        ],
        'transferred_info' => [
            'title' => 'Message Transferred',
            'body' => 'Your message ":subject" has been transferred to another user.',
        ],
        'default' => [
            'title' => 'Notification',
            'body' => 'You have a new notification about the message ":subject"',
        ],
    ],

    // Exceções
    'exceptions' => [
        'cannot_reply_to_message' => 'User cannot reply to this message',
        'cannot_redeliver_invalid_status' => 'Message cannot be redelivered - invalid status',
        'only_pending_can_be_approved' => 'Only pending messages can be approved',
        'no_permission_to_approve' => 'User does not have permission to approve this message',
        'message_not_pending_approval' => 'Message is not pending approval',
        'cannot_reject_own_message' => 'User cannot reject their own message',
        'not_authorized_to_reject' => 'User is not authorized to reject this message',
        'no_approver_found' => 'No approver found for this type of message',
        'no_alternative_approver_found' => 'No alternative approver found',
        'cannot_transfer_current_status' => 'Message cannot be transferred in the current status',
        'only_current_recipient_can_transfer' => 'Only the current recipient can transfer the message',
        'cannot_transfer_to_self' => 'You cannot transfer the message to yourself',
    ],
];
