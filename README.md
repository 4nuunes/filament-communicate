# Filament Communicate

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alessandronuunes/filament-communicate.svg?style=flat-square)](https://packagist.org/packages/alessandronuunes/filament-communicate)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/alessandronuunes/filament-communicate/run-tests?label=tests)](https://github.com/alessandronuunes/filament-communicate/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/alessandronuunes/filament-communicate/Check%20&%20fix%20styling?label=code%20style)](https://github.com/alessandronuunes/filament-communicate/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/alessandronuunes/filament-communicate.svg?style=flat-square)](https://packagist.org/packages/alessandronuunes/filament-communicate)

A comprehensive internal messaging system for Filament Admin Panel with approval workflows, hierarchical messaging, and advanced permission controls.

## 🚀 Features

### Core Messaging System
- **Hierarchical Messages**: Original messages and threaded replies
- **Message Types**: Configurable message categories with custom settings
- **Priority Levels**: Low, Normal, High, and Urgent priorities with visual indicators
- **File Attachments**: Support for multiple file types with validation
- **Message Status Tracking**: Complete lifecycle from draft to archived

### Approval Workflow
- **Configurable Approval**: Messages can require approval before delivery
- **Supervisor Controls**: Approve/reject messages with reasons
- **Automatic Routing**: Smart message routing based on approval requirements
- **Reply Exemption**: Replies bypass approval requirements for faster communication

### Permission System
- **Role-Based Access**: Super Admin, Supervisor, and User roles
- **Granular Permissions**: Control who can see, approve, and manage messages
- **Ownership Rules**: Users see their own messages, supervisors manage approvals
- **Transfer Capabilities**: Messages can be transferred between users

### Advanced Features
- **Unique Message Codes**: Automatic generation with customizable formats
- **Read Receipts**: Track message delivery and read status
- **Statistics Dashboard**: Message counts, badges, and analytics
- **Notification System**: Real-time notifications for message events
- **Localization**: Full Portuguese (Brazil) and English support

## 📋 Requirements

- PHP 8.2 or higher
- Laravel 10.0 or higher
- Filament 3.0 or higher
- MySQL 5.7+ or PostgreSQL 10+

## 📦 Installation

1. **Install the package via Composer:**

```bash
composer require alessandronuunes/filament-communicate
```

2. **Publish and run the migrations:**

```bash
php artisan vendor:publish --tag="filament-communicate-migrations"
php artisan migrate
```

3. **Publish the configuration file (optional):**

```bash
php artisan vendor:publish --tag="filament-communicate-config"
```

4. **Register the plugin in your Filament Panel:**

```php
use Alessandronuunes\FilamentCommunicate\FilamentCommunicatePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentCommunicatePlugin::make(),
        ]);
}
```

## ⚙️ Configuration

### Basic Configuration

The package comes with sensible defaults, but you can customize everything in `config/filament-communicate.php`:

```php
return [
    // Role-based permissions
    'super_admin_roles' => ['super_admin'],
    'supervisor_roles' => ['supervisor'],
    'user_roles' => ['atendente'],
    
    // Message code format
    'message_code' => [
        'prefix' => 'MSG',
        'sequence_digits' => 6,
        'include_year' => false,
    ],
    
    // File upload settings
    'storage' => [
        'disk' => 'public',
        'max_file_size' => 10240, // 10MB
        'max_files' => 5,
    ],
];
```

### Navigation Configuration

Customize menu placement and appearance:

```php
'navigation' => [
    'message_resource' => [
        'group' => 'Communication',
        'sort' => 10,
        'icon' => 'heroicon-o-envelope',
    ],
    'message_type_resource' => [
        'group' => 'Settings',
        'sort' => 20,
        'icon' => 'heroicon-o-rectangle-group',
    ],
],
```

## 🎯 Usage

### Creating Message Types

1. Navigate to **Settings > Message Types**
2. Create categories like "Internal Memo", "Announcement", "Request"
3. Configure approval requirements per type
4. Set default priorities and permissions

### Sending Messages

1. Go to **Communication > Messages**
2. Click **New Message**
3. Select message type and recipient
4. Add subject, content, and attachments
5. Choose priority level
6. Send or save as draft

### Approval Workflow

**For Supervisors:**
1. View pending messages in the **Messages** list
2. Click on a message to review details
3. Use **Approve** or **Reject** actions
4. Add approval reasons when needed

**For Users:**
- Messages requiring approval show "Pending Approval" status
- Approved messages are automatically delivered
- Rejected messages can be edited and resubmitted

### Message Threading

- Click **Reply** on any message to start a thread
- Replies are grouped with the original message
- Thread participants can see all related messages
- Reply counts are displayed in message lists

## 🏗️ Architecture

### Models

- **Message**: Core message entity with status, priority, and relationships
- **MessageType**: Configurable message categories
- **MessageApproval**: Approval workflow tracking
- **MessageTransfer**: Message transfer history

### Services

- **MessageService**: Main business logic coordinator
- **MessageApprovalService**: Handles approval workflows
- **MessageDeliveryService**: Manages message delivery
- **MessageReplyService**: Processes message replies
- **MessageStatisticsService**: Generates analytics and badges
- **MessageTransferService**: Handles message transfers

### Enums

- **MessageStatus**: Draft, Pending, Approved, Rejected, Sent, Read, Archived
- **MessagePriority**: Low, Normal, High, Urgent

### Actions

- **ApproveMessageAction**: Supervisor approval functionality
- **RejectMessageAction**: Message rejection with reasons
- **ReplyMessageAction**: Create threaded replies
- **TransferMessageAction**: Transfer messages between users

## 🔐 Permission System

### Role Definitions

**Super Admin:**
- Full access to all messages
- Can manage message types
- System configuration access

**Supervisor:**
- Can approve/reject pending messages
- Cannot approve their own messages
- Sees messages requiring approval

**User:**
- Sees only their own messages (sent/received)
- Can create and reply to messages
- Limited to assigned message types

### Visibility Rules

```php
// Supervisors see pending messages (except their own)
'supervisor_visibility' => [
    'allowed_statuses' => [MessageStatus::PENDING],
    'exclude_own_messages' => true,
],
```

## 📊 Message Status Flow

```
Draft → Pending Approval → Approved → Sent → Read → Archived
                     ↓
                  Rejected
```

**Status Descriptions:**
- **Draft**: Message saved but not sent
- **Pending**: Awaiting supervisor approval
- **Approved**: Approved and ready for delivery
- **Rejected**: Rejected by supervisor with reason
- **Sent**: Delivered to recipient
- **Read**: Opened by recipient
- **Archived**: Moved to archive

## 🎨 Customization

### Custom Message Codes

```php
'message_code' => [
    'prefix' => 'VMIX',
    'include_year' => true,
    'year_format' => 'Y',
    'sequence_digits' => 8,
    'custom_format' => '{prefix}-{year}-{sequence}', // VMIX-2024-00000001
],
```

### File Upload Restrictions

```php
'storage' => [
    'allowed_file_types' => [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ],
],
```

## 🧪 Testing

```bash
composer test
```

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔒 Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 💡 Use Cases

Perfect for organizations that need:

- **Corporate Communication**: Internal memos and announcements
- **Approval Workflows**: Document and request approvals
- **Help Desk Systems**: Internal support ticket management
- **Project Communication**: Team collaboration with oversight
- **Compliance Tracking**: Auditable communication trails
- **Multi-department Coordination**: Cross-functional messaging

---

**Built with ❤️ for the Filament community**