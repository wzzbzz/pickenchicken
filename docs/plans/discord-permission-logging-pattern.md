# Porting the RBAC/audit-log pattern to a Discord bot (conceptual)

Status: conceptual sketch, not implemented. Source: PickenChicken API's in-progress
permissions system (`RequiresPermission` attribute + `PermissionCheckListener` +
`ActionLog` entity — see `api/src/Security/RequiresPermission.php`,
`api/src/EventListener/PermissionCheckListener.php`).

## Pattern being ported

1. Every action declares its access requirement (permission slug / logged-in-only / public).
2. One centralized enforcement point runs before every action executes.
3. Every action attempt is logged — allowed or denied — unconditionally.

## discord.js v14 translation

**1. Permission declared per command** (replaces `RequiresPermission`)

```js
// commands/placeBets.js
const { SlashCommandBuilder } = require('discord.js');

module.exports = {
  data: new SlashCommandBuilder()
    .setName('place-bets')
    .setDescription('Place a bet'),
  access: { permission: 'place_bets' }, // { public: true } or {} (logged-in only) are the other two states
  async execute(interaction) {
    await interaction.reply('Bet placed.');
  },
};
```

**2. Central enforcement + logging** (replaces `PermissionCheckListener`)

```js
// events/interactionCreate.js
const { Events } = require('discord.js');
const db = require('../db');
const { getOrCreateAppUser, userHasPermission } = require('../services/permissions');

module.exports = {
  name: Events.InteractionCreate,
  async execute(interaction) {
    if (!interaction.isChatInputCommand()) return;

    const command = interaction.client.commands.get(interaction.commandName);
    if (!command) return;

    const access = command.access ?? {};
    let allowed = true;
    let denyReason = null;
    let appUser = null;

    if (!access.public) {
      appUser = await getOrCreateAppUser(interaction.user.id);
      if (access.permission && !(await userHasPermission(appUser, access.permission))) {
        allowed = false;
        denyReason = 'missing_permission';
      }
    }

    await db('action_log').insert({
      discord_user_id: interaction.user.id,
      guild_id: interaction.guildId,
      command: interaction.commandName,
      permission_required: access.permission ?? null,
      allowed,
      deny_reason: denyReason,
      created_at: new Date(),
    });

    if (!allowed) {
      return interaction.reply({ content: 'You do not have permission to do that.', ephemeral: true });
    }

    try {
      await command.execute(interaction, appUser);
    } catch (err) {
      console.error(err);
      await interaction.reply({ content: 'Something went wrong.', ephemeral: true });
    }
  },
};
```

**3. Roles/permissions storage** (replaces `Role`/`Permission` entities)

```sql
CREATE TABLE permissions (id serial PRIMARY KEY, slug text UNIQUE NOT NULL);
CREATE TABLE roles (id serial PRIMARY KEY, name text UNIQUE NOT NULL);
CREATE TABLE role_permissions (role_id int REFERENCES roles, permission_id int REFERENCES permissions, PRIMARY KEY (role_id, permission_id));
CREATE TABLE user_roles (discord_user_id text NOT NULL, role_id int REFERENCES roles, PRIMARY KEY (discord_user_id, role_id));
CREATE TABLE action_log (
  id serial PRIMARY KEY,
  discord_user_id text NOT NULL,
  guild_id text,
  command text NOT NULL,
  permission_required text,
  allowed boolean NOT NULL,
  deny_reason text,
  created_at timestamptz NOT NULL
);
```

## Notes / divergences from the Symfony version

- No implicit "no `access` field = public." Missing `access` should throw at command-load
  time, mirroring the listener's "every route must declare" invariant.
- Log denies the same as allows — the audit trail needs to be complete either way.
- Discord already has a native permission system (guild roles, channel overwrites,
  `default_member_permissions` on slash commands). Decide whether the custom RBAC table
  replaces that or layers on top for checks Discord can't express (cross-guild, app-specific).
- Only slash commands are covered above — buttons, select menus, and other component
  interactions need the same guard if this pattern is adopted for real.
