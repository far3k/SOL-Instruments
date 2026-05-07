import {
  Callout,
  Divider,
  Grid,
  H1,
  H2,
  H3,
  Pill,
  Row,
  Stack,
  Stat,
  Table,
  Text,
} from "cursor/canvas";

export default function MusicInstrumentRentalProposal() {
  return (
    <Stack gap={20}>
      <H1>Project Proposal: Music Instrument Rental Platform</H1>
      <Text>
        This is a presentation-ready proposal for Requirements Engineering and Project
        Management, designed to define a clear and defensible path before development begins.
      </Text>

      <Grid columns={4} gap={12}>
        <Stat label="Timeline" value="2 sprints / 2 weeks" />
        <Stat label="Core Features" value="Must-have scope locked" />
        <Stat label="User Roles" value="3 primary personas" />
        <Stat label="Technology" value="Core PHP + MySQL" />
      </Grid>

      <Divider />
      <H2>1) Scope and Key Requirements</H2>
      <Grid columns={2} gap={16}>
        <Stack gap={8}>
          <H3>Customer Features (Must Have)</H3>
          <Text>- Secure registration and login</Text>
          <Text>- Browse and search instruments</Text>
          <Text>- Filter by category</Text>
          <Text>- Submit rental requests with date range and purpose</Text>
          <Text>- Track request status and cancel pending requests</Text>
          <Text>- Update profile information</Text>
        </Stack>
        <Stack gap={8}>
          <H3>Admin Features (Must Have)</H3>
          <Text>- Instrument management with image uploads</Text>
          <Text>- Category management</Text>
          <Text>- Review, approve/reject, and complete rentals</Text>
          <Text>- User management with block/unblock</Text>
          <Text>- Availability tracking based on rental dates</Text>
        </Stack>
      </Grid>

      <Callout tone="info" title="General Rules">
        <Text>
          Responsive design, form validation, date validation, secure authentication, and
          authorization checks are non-negotiable baseline requirements.
        </Text>
      </Callout>

      <Divider />
      <H2>2) Primary User Personas</H2>
      <Table
        headers={["Persona", "Primary Need", "Pain Point", "Goal"]}
        rows={[
          [
            "Maryam - Beginner Student",
            "Affordable short-term instrument rental",
            "Unsure if she will continue learning",
            "Try before buying",
          ],
          [
            "Reza - Music Instructor",
            "Fast access to quality instruments for students",
            "Manual rental handling is slow and error-prone",
            "Reliable and predictable booking flow",
          ],
          [
            "Academy Admin",
            "Centralized control over stock and requests",
            "Booking conflicts and human mistakes",
            "Structured, auditable operations",
          ],
        ]}
      />

      <Divider />
      <H2>3) Proposed Wireframes (Key Screens)</H2>
      <Grid columns={2} gap={16}>
        <Stack gap={8}>
          <H3>Customer Side</H3>
          <Text>1. Instrument Listing (card grid + filters + search)</Text>
          <Text>2. Instrument Detail (image, specs, daily price, condition)</Text>
          <Text>3. Rental Request Form (start/end date + purpose)</Text>
          <Text>4. My Rentals (pending/approved/rejected/completed)</Text>
        </Stack>
        <Stack gap={8}>
          <H3>Admin Side</H3>
          <Text>1. Admin Dashboard (KPIs + new requests)</Text>
          <Text>2. Instrument Management (CRUD + image upload)</Text>
          <Text>3. Category Management (CRUD)</Text>
          <Text>4. User Management (status and block controls)</Text>
        </Stack>
      </Grid>
      <Text tone="secondary" size="small">
        Note: This proposal defines wireframe scope and screen structure. You should still
        produce actual visual wireframes in Figma/Excalidraw/draw.io for final submission.
      </Text>

      <Divider />
      <H2>4) Proposed ERD (Initial Database Design)</H2>
      <Table
        headers={["Table", "Key Fields", "Relationships"]}
        rows={[
          [
            "users",
            "id, full_name, email, password_hash, role, status, phone",
            "One-to-many with rental_requests",
          ],
          [
            "categories",
            "id, name, description",
            "One-to-many with instruments",
          ],
          [
            "instruments",
            "id, category_id, name, daily_price, condition, description, image_url, is_active",
            "Many-to-one with categories / one-to-many with rental_requests",
          ],
          [
            "rental_requests",
            "id, user_id, instrument_id, start_date, end_date, purpose, status, admin_note, requested_at, decided_at",
            "Many-to-one with users and instruments",
          ],
          [
            "rental_status_logs",
            "id, rental_request_id, old_status, new_status, changed_by_user_id, changed_at",
            "Many-to-one with rental_requests",
          ],
        ]}
      />
      <Text tone="secondary" size="small">
        Status values: pending, approved, rejected, completed, cancelled. Date rules and
        booking overlap checks are enforced in the service layer.
      </Text>

      <Divider />
      <H2>5) User Stories + Acceptance Criteria</H2>
      <Stack gap={10}>
        <Text>
          As a user, I want to register/login securely, so that I can manage my rentals.
        </Text>
        <Text tone="secondary">
          AC: Passwords are hashed, duplicate email registration is blocked, and a valid
          session is created after login.
        </Text>
        <Text>
          As a user, I want to search and filter instruments, so that I can find a suitable instrument quickly.
        </Text>
        <Text tone="secondary">
          AC: Search by name/category, category filters work, and only rentable instruments
          are shown.
        </Text>
        <Text>
          As a user, I want to request rental dates, so that I can reserve an instrument for my learning period.
        </Text>
        <Text tone="secondary">
          AC: End date must be after start date, past dates are rejected, and initial status is pending.
        </Text>
        <Text>
          As an admin, I want to approve/reject requests, so that inventory is allocated correctly.
        </Text>
        <Text tone="secondary">
          AC: Admin can only approve/reject pending requests, and rejection reason can be saved.
        </Text>
        <Text>
          As an admin, I want to mark rentals as returned, so that instrument availability is updated.
        </Text>
        <Text tone="secondary">
          AC: Only approved requests can be marked completed, and instrument availability is restored.
        </Text>
        <Text>
          As a user, I want to view my own rental requests only, so that my account data remains private.
        </Text>
        <Text tone="secondary">
          AC: A user can access only their own requests; direct URL access to another user's request is denied.
        </Text>
        <Text>
          As an admin, I want to manage instruments and categories, so that the catalog stays accurate and up to date.
        </Text>
        <Text tone="secondary">
          AC: Admin can create/edit/delete records, and category deletion is blocked when instruments are linked.
        </Text>
        <Text>
          As an admin, I want to block/unblock user accounts, so that policy violations can be controlled.
        </Text>
        <Text tone="secondary">
          AC: Blocked users cannot log in or submit requests, while existing historical requests remain visible to admin.
        </Text>
        <Text>
          As the system, I need rental date overlap checks, so that one instrument cannot be double-booked.
        </Text>
        <Text tone="secondary">
          AC: New or approved requests are rejected if they overlap an approved rental window for the same instrument.
        </Text>
      </Stack>

      <Divider />
      <H2>6) Sprint Plan (2 Sprints)</H2>
      <Grid columns={2} gap={16}>
        <Stack gap={8}>
          <Row gap={8}>
            <H3>Sprint 1</H3>
            <Pill tone="info">Week 1</Pill>
          </Row>
          <Text>- Database design + ERD + SQL schema scripts</Text>
          <Text>- Auth (Register/Login/Session/Role)</Text>
          <Text>- Instrument list/detail/search/filter</Text>
          <Text>- Rental request submission + date validation</Text>
          <Text>- Trello Backlog to UpNext ready for execution</Text>
        </Stack>
        <Stack gap={8}>
          <Row gap={8}>
            <H3>Sprint 2</H3>
            <Pill tone="success">Week 2</Pill>
          </Row>
          <Text>- Admin panel (instrument/category CRUD)</Text>
          <Text>- End-to-end rental request management</Text>
          <Text>- User management (block/unblock)</Text>
          <Text>- Complete My Rentals and cancel pending flow</Text>
          <Text>- Final core-feature validation and demo readiness</Text>
        </Stack>
      </Grid>

      <Divider />
      <H2>7) Suggested Task Board Structure</H2>
      <Text>
        Columns: Backlog → UpNext → InProgress → Done
      </Text>
      <Text tone="secondary">
        Definition of Done: merged code, validation completed, core manual user flow passed,
        and role-based permissions verified.
      </Text>

      <Divider />
      <H2>8) Pro Add-on Upgrade Scope (Basic to Pro)</H2>
      <Grid columns={2} gap={16}>
        <Stack gap={8}>
          <H3>Public Website Expansion</H3>
          <Text>- Full homepage sections (hero, featured rentals, featured products)</Text>
          <Text>- About, Contact, FAQ, Terms, and Privacy pages</Text>
          <Text>- Unified navigation and footer across all modules</Text>
        </Stack>
        <Stack gap={8}>
          <H3>Store Expansion</H3>
          <Text>- Enhanced store listing and product detail pages</Text>
          <Text>- Cart, checkout, and order history flow</Text>
          <Text>- Admin order management and status updates</Text>
        </Stack>
      </Grid>
      <Table
        headers={["Pro Table Additions", "Purpose"]}
        rows={[
          ["orders", "Store customer orders and lifecycle status"],
          ["order_items", "Line items per order"],
          ["contact_messages", "Store website contact/support form submissions"],
          ["faq_items", "Manage FAQ entries from admin panel"],
        ]}
      />
      <Callout tone="info" title="Integration Rule">
        <Text>
          The project remains one unified system. Pro features are built as an extension
          layer on top of the Basic scope, then merged into the same final branch and
          release package.
        </Text>
      </Callout>

      <Callout tone="success" title="Next Step">
        <Text>
          This proposal is for group1.
        </Text>
      </Callout>
    </Stack>
  );
}
