# Database Schema Description

The database schema for the ERepair Booking Platform is designed to modernize the management of electronic repair services and enhance transparency between device owners and technicians. Built on MySQL 5.7+ with InnoDB engine and UTF8MB4 character encoding, it comprises 16 interconnected tables that manage user identities, shop profiles, repair transactions, and service workflows.

The **users** table serves as the central repository for all user accounts, storing core authentication details and linking to role-specific profiles for customers, shop owners, technicians, and administrators. This unified user management approach is supported by three authentication and security tables: **sessions** for token-based authentication with 7-day expiration, **email_verifications** for 6-digit verification codes with 5-minute expiry, and **password_resets** for secure password recovery with 12-character codes and 15-minute expiry.

Shop and business management is handled through the **shop_owners** table, which stores shop owner profiles along with embedded business documentation including ID types, ID numbers, ID expiry dates, front and back ID files, business permit files, and approval status. The **repair_shops** table manages physical shop locations with coordinates (latitude and longitude), contact information, logos, descriptions, and links to shop owners. This design allows shop owners to maintain their business credentials and verification documents directly within their profile records.

Technician management utilizes the **technicians** table, which links technician user accounts to shop owners and specific repair shops.

Service offerings are managed through two tables: the **services** table links services to specific repair shops with pricing, duration, and descriptions, while the **shop_services** table provides an alternative legacy structure linking services directly to shop owners. This dual approach supports both shop-specific and owner-level service management.

The core booking workflow is tracked through the **bookings** table, which serves as the primary repair transaction record. This comprehensive table links customers to shops and technicians, records device information (type, description, issue description, and photos), manages a 10-stage status workflow (pending_review, awaiting_customer_confirmation, confirmed_by_customer, approved, assigned, in_progress, completed, cancelled_by_customer, rejected, cancelled), handles scheduling with scheduled_at timestamps and duration_minutes, stores pricing information including total_price, estimated_cost, and estimated_time_days, and maintains diagnostic notes and general notes. The **booking_history** table provides a complete audit trail, tracking every status change with timestamps, user references, and change notes, ensuring full transparency and accountability throughout the repair lifecycle.

Quality control and feedback are managed through a comprehensive review system: the **reviews** table stores individual customer reviews with 1-5 star ratings and comments, linked to specific bookings, customers, shops, and technicians, with a unique constraint ensuring one review per completed booking. The **shop_ratings** and **technician_ratings** tables maintain aggregated rating statistics including total reviews, average ratings, and total rating sums, automatically calculated through stored procedures to provide real-time performance metrics.

Communication and user engagement are facilitated through the **notifications** table, which manages in-app notifications with titles, messages, types, links, and read status, ensuring users stay informed about booking updates, status changes, and important platform events.

Additionally, the **shop_items** table enables e-commerce functionality, allowing repair shops to manage inventory of products and parts with item names, descriptions, prices, stock quantities, categories, images, and availability status, expanding the platform beyond repair services to include parts and accessory sales.

The interconnected structure, enforced through foreign key constraints with appropriate cascade and set-null behaviors, enables smooth scheduling, accurate real-time tracking, and efficient operational management. Strategic indexing on frequently queried columns such as email addresses, session tokens, booking statuses, location coordinates, and rating averages ensures optimal query performance. The schema also includes three database views (view_active_bookings, view_shop_performance, view_pending_approvals) and three stored procedures (update_shop_ratings, update_technician_ratings, cleanup_expired_records) for enhanced functionality and automated maintenance, with a daily cleanup event to remove expired sessions and verification codes.

This comprehensive database design supports the entire repair service ecosystem across the province of Bohol, providing a robust foundation for connecting customers with qualified repair technicians while maintaining data integrity, security, and operational efficiency.



