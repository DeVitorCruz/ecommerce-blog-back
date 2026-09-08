import pathlib, re

tenant_path = pathlib.Path('database/migrations/tenant')

# Tables that live in platform DB (mercatura)
# No FK constraints possible from tenant DB
platform_refs = {
    'user_id':        'platform.users',
    'seller_id':      'platform.sellers',
    'approved_by':    'platform.users',
    'requested_by':   'platform.users',
    'processed_by':   'platform.users',
    'suggested_by':   'platform.users',
}

fixed   = []
skipped = []

for f in sorted(tenant_path.glob('*.php')):
    src       = f.read_text()
    lines     = src.split('\n')
    new_lines = []
    changed   = False

    for line in lines:
        stripped = line.strip()

        # Only process lines with foreignId + constrained
        if '->foreignId(' not in line or '->constrained' not in line:
            new_lines.append(line)
            continue

        # Extract column name
        col_match = re.search(r"->foreignId\('(\w+)'\)", line)
        if not col_match:
            new_lines.append(line)
            continue

        col = col_match.group(1)

        # Only fix cross-DB references
        if col not in platform_refs:
            new_lines.append(line)
            continue

        # Preserve indentation
        indent = len(line) - len(line.lstrip())
        spaces = ' ' * indent
        nullable = '->nullable()' in line

        if nullable:
            new_line = (
                f"{spaces}$table->unsignedBigInteger('{col}')"
                f"->nullable();"
                f" // cross-DB → {platform_refs[col]}"
            )
        else:
            new_line = (
                f"{spaces}$table->unsignedBigInteger('{col}');"
                f" // cross-DB → {platform_refs[col]}"
            )

        new_lines.append(new_line)
        changed = True
        print(f"  [{f.name}] fixed: {col}")

    if changed:
        f.write_text('\n'.join(new_lines))
        fixed.append(f.name)
    else:
        skipped.append(f.name)

print(f"\n✅ Fixed {len(fixed)} files:")
for name in fixed:
    print(f"   {name}")

print(f"\n⏭  Skipped {len(skipped)} files:")
for name in skipped:
    print(f"   {name}")