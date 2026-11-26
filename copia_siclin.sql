--
-- PostgreSQL database dump
--

\restrict 9YAh3iGvevMUVGfGtozLbkGvZRgQHJTwOxwlq8FghfzNF3VS02NpRar1wmkaoi9

-- Dumped from database version 16.10
-- Dumped by pg_dump version 16.10

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_cohort_id_foreign;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_access_approved_by_foreign;
ALTER TABLE IF EXISTS ONLY public.swap_requests DROP CONSTRAINT IF EXISTS swap_requests_target_place_id_foreign;
ALTER TABLE IF EXISTS ONLY public.swap_requests DROP CONSTRAINT IF EXISTS swap_requests_target_id_foreign;
ALTER TABLE IF EXISTS ONLY public.swap_requests DROP CONSTRAINT IF EXISTS swap_requests_source_place_id_foreign;
ALTER TABLE IF EXISTS ONLY public.swap_requests DROP CONSTRAINT IF EXISTS swap_requests_source_id_foreign;
ALTER TABLE IF EXISTS ONLY public.shifts DROP CONSTRAINT IF EXISTS shifts_tutor_id_foreign;
ALTER TABLE IF EXISTS ONLY public.shifts DROP CONSTRAINT IF EXISTS shifts_batch_id_foreign;
ALTER TABLE IF EXISTS ONLY public.shifts DROP CONSTRAINT IF EXISTS shifts_assignment_id_foreign;
ALTER TABLE IF EXISTS ONLY public.shift_batches DROP CONSTRAINT IF EXISTS shift_batches_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.scenario_studies DROP CONSTRAINT IF EXISTS scenario_studies_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.reports DROP CONSTRAINT IF EXISTS reports_user_id_foreign;
ALTER TABLE IF EXISTS ONLY public.reports DROP CONSTRAINT IF EXISTS reports_run_id_foreign;
ALTER TABLE IF EXISTS ONLY public.reports DROP CONSTRAINT IF EXISTS reports_cycle_id_foreign;
ALTER TABLE IF EXISTS ONLY public.reports DROP CONSTRAINT IF EXISTS reports_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.places DROP CONSTRAINT IF EXISTS places_tutor_id_foreign;
ALTER TABLE IF EXISTS ONLY public.places DROP CONSTRAINT IF EXISTS places_hospital_id_foreign;
ALTER TABLE IF EXISTS ONLY public.places DROP CONSTRAINT IF EXISTS places_cycle_id_foreign;
ALTER TABLE IF EXISTS ONLY public.otp_challenges DROP CONSTRAINT IF EXISTS otp_challenges_user_id_foreign;
ALTER TABLE IF EXISTS ONLY public.optimization_runs DROP CONSTRAINT IF EXISTS optimization_runs_cycle_id_foreign;
ALTER TABLE IF EXISTS ONLY public.optimization_runs DROP CONSTRAINT IF EXISTS optimization_runs_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.incidents DROP CONSTRAINT IF EXISTS incidents_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.incidents DROP CONSTRAINT IF EXISTS incidents_assignment_id_foreign;
ALTER TABLE IF EXISTS ONLY public.cycles DROP CONSTRAINT IF EXISTS cycles_specialty_id_foreign;
ALTER TABLE IF EXISTS ONLY public.cycles DROP CONSTRAINT IF EXISTS cycles_cohort_id_foreign;
ALTER TABLE IF EXISTS ONLY public.clinical_records DROP CONSTRAINT IF EXISTS clinical_records_recorded_by_foreign;
ALTER TABLE IF EXISTS ONLY public.clinical_records DROP CONSTRAINT IF EXISTS clinical_records_assignment_id_foreign;
ALTER TABLE IF EXISTS ONLY public.availabilities DROP CONSTRAINT IF EXISTS availabilities_tutor_id_foreign;
ALTER TABLE IF EXISTS ONLY public.audit_logs DROP CONSTRAINT IF EXISTS audit_logs_user_id_foreign;
ALTER TABLE IF EXISTS ONLY public.assignments DROP CONSTRAINT IF EXISTS assignments_user_id_foreign;
ALTER TABLE IF EXISTS ONLY public.assignments DROP CONSTRAINT IF EXISTS assignments_tutor_id_foreign;
ALTER TABLE IF EXISTS ONLY public.assignments DROP CONSTRAINT IF EXISTS assignments_run_id_foreign;
ALTER TABLE IF EXISTS ONLY public.assignments DROP CONSTRAINT IF EXISTS assignments_place_id_foreign;
ALTER TABLE IF EXISTS ONLY public.assignments DROP CONSTRAINT IF EXISTS assignments_cycle_id_foreign;
ALTER TABLE IF EXISTS ONLY public.assignment_history DROP CONSTRAINT IF EXISTS assignment_history_tutor_id_foreign;
ALTER TABLE IF EXISTS ONLY public.assignment_history DROP CONSTRAINT IF EXISTS assignment_history_run_id_foreign;
ALTER TABLE IF EXISTS ONLY public.assignment_history DROP CONSTRAINT IF EXISTS assignment_history_place_id_foreign;
ALTER TABLE IF EXISTS ONLY public.assignment_history DROP CONSTRAINT IF EXISTS assignment_history_changed_by_foreign;
ALTER TABLE IF EXISTS ONLY public.assignment_history DROP CONSTRAINT IF EXISTS assignment_history_assignment_id_foreign;
ALTER TABLE IF EXISTS ONLY public.applications DROP CONSTRAINT IF EXISTS applications_user_id_foreign;
ALTER TABLE IF EXISTS ONLY public.applications DROP CONSTRAINT IF EXISTS applications_cycle_id_foreign;
ALTER TABLE IF EXISTS ONLY public.application_versions DROP CONSTRAINT IF EXISTS application_versions_changed_by_foreign;
ALTER TABLE IF EXISTS ONLY public.application_versions DROP CONSTRAINT IF EXISTS application_versions_application_id_foreign;
ALTER TABLE IF EXISTS ONLY public.academic_requirements DROP CONSTRAINT IF EXISTS academic_requirements_specialty_id_foreign;
ALTER TABLE IF EXISTS ONLY public.academic_requirements DROP CONSTRAINT IF EXISTS academic_requirements_cohort_id_foreign;
DROP INDEX IF EXISTS public.users_email_normalized;
DROP INDEX IF EXISTS public.sessions_user_id_index;
DROP INDEX IF EXISTS public.sessions_last_activity_index;
DROP INDEX IF EXISTS public.otp_challenges_expires_at_index;
DROP INDEX IF EXISTS public.otp_challenges_account_hash_index;
DROP INDEX IF EXISTS public.notifications_notifiable_type_notifiable_id_index;
DROP INDEX IF EXISTS public.jobs_queue_index;
DROP INDEX IF EXISTS public.failed_jobs_connection_queue_failed_at_index;
DROP INDEX IF EXISTS public.cache_locks_expiration_index;
DROP INDEX IF EXISTS public.cache_expiration_index;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_pkey;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_matricula_unique;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_email_unique;
ALTER TABLE IF EXISTS ONLY public.swap_requests DROP CONSTRAINT IF EXISTS swap_requests_pkey;
ALTER TABLE IF EXISTS ONLY public.specialties DROP CONSTRAINT IF EXISTS specialties_pkey;
ALTER TABLE IF EXISTS ONLY public.specialties DROP CONSTRAINT IF EXISTS specialties_name_unique;
ALTER TABLE IF EXISTS ONLY public.shifts DROP CONSTRAINT IF EXISTS shifts_pkey;
ALTER TABLE IF EXISTS ONLY public.shift_batches DROP CONSTRAINT IF EXISTS shift_batches_pkey;
ALTER TABLE IF EXISTS ONLY public.settings DROP CONSTRAINT IF EXISTS settings_pkey;
ALTER TABLE IF EXISTS ONLY public.sessions DROP CONSTRAINT IF EXISTS sessions_pkey;
ALTER TABLE IF EXISTS ONLY public.scenario_studies DROP CONSTRAINT IF EXISTS scenario_studies_pkey;
ALTER TABLE IF EXISTS ONLY public.reports DROP CONSTRAINT IF EXISTS reports_token_unique;
ALTER TABLE IF EXISTS ONLY public.reports DROP CONSTRAINT IF EXISTS reports_pkey;
ALTER TABLE IF EXISTS ONLY public.places DROP CONSTRAINT IF EXISTS places_pkey;
ALTER TABLE IF EXISTS ONLY public.places DROP CONSTRAINT IF EXISTS places_cycle_id_hospital_id_unique;
ALTER TABLE IF EXISTS ONLY public.password_reset_tokens DROP CONSTRAINT IF EXISTS password_reset_tokens_pkey;
ALTER TABLE IF EXISTS ONLY public.otp_limits DROP CONSTRAINT IF EXISTS otp_limits_pkey;
ALTER TABLE IF EXISTS ONLY public.otp_challenges DROP CONSTRAINT IF EXISTS otp_challenges_pkey;
ALTER TABLE IF EXISTS ONLY public.optimization_runs DROP CONSTRAINT IF EXISTS optimization_runs_request_id_unique;
ALTER TABLE IF EXISTS ONLY public.optimization_runs DROP CONSTRAINT IF EXISTS optimization_runs_pkey;
ALTER TABLE IF EXISTS ONLY public.notifications DROP CONSTRAINT IF EXISTS notifications_pkey;
ALTER TABLE IF EXISTS ONLY public.migrations DROP CONSTRAINT IF EXISTS migrations_pkey;
ALTER TABLE IF EXISTS ONLY public.jobs DROP CONSTRAINT IF EXISTS jobs_pkey;
ALTER TABLE IF EXISTS ONLY public.job_batches DROP CONSTRAINT IF EXISTS job_batches_pkey;
ALTER TABLE IF EXISTS ONLY public.incidents DROP CONSTRAINT IF EXISTS incidents_pkey;
ALTER TABLE IF EXISTS ONLY public.hospitals DROP CONSTRAINT IF EXISTS hospitals_pkey;
ALTER TABLE IF EXISTS ONLY public.hospitals DROP CONSTRAINT IF EXISTS hospitals_name_unique;
ALTER TABLE IF EXISTS ONLY public.failed_jobs DROP CONSTRAINT IF EXISTS failed_jobs_uuid_unique;
ALTER TABLE IF EXISTS ONLY public.failed_jobs DROP CONSTRAINT IF EXISTS failed_jobs_pkey;
ALTER TABLE IF EXISTS ONLY public.cycles DROP CONSTRAINT IF EXISTS cycles_pkey;
ALTER TABLE IF EXISTS ONLY public.cohorts DROP CONSTRAINT IF EXISTS cohorts_pkey;
ALTER TABLE IF EXISTS ONLY public.cohorts DROP CONSTRAINT IF EXISTS cohorts_name_unique;
ALTER TABLE IF EXISTS ONLY public.clinical_records DROP CONSTRAINT IF EXISTS clinical_records_pkey;
ALTER TABLE IF EXISTS ONLY public.clinical_records DROP CONSTRAINT IF EXISTS clinical_records_assignment_id_date_unique;
ALTER TABLE IF EXISTS ONLY public.cache DROP CONSTRAINT IF EXISTS cache_pkey;
ALTER TABLE IF EXISTS ONLY public.cache_locks DROP CONSTRAINT IF EXISTS cache_locks_pkey;
ALTER TABLE IF EXISTS ONLY public.availabilities DROP CONSTRAINT IF EXISTS availabilities_pkey;
ALTER TABLE IF EXISTS ONLY public.audit_logs DROP CONSTRAINT IF EXISTS audit_logs_pkey;
ALTER TABLE IF EXISTS ONLY public.assignments DROP CONSTRAINT IF EXISTS assignments_pkey;
ALTER TABLE IF EXISTS ONLY public.assignments DROP CONSTRAINT IF EXISTS assignments_cycle_id_user_id_unique;
ALTER TABLE IF EXISTS ONLY public.assignment_history DROP CONSTRAINT IF EXISTS assignment_history_pkey;
ALTER TABLE IF EXISTS ONLY public.applications DROP CONSTRAINT IF EXISTS applications_receipt_unique;
ALTER TABLE IF EXISTS ONLY public.applications DROP CONSTRAINT IF EXISTS applications_pkey;
ALTER TABLE IF EXISTS ONLY public.applications DROP CONSTRAINT IF EXISTS applications_cycle_id_user_id_unique;
ALTER TABLE IF EXISTS ONLY public.application_versions DROP CONSTRAINT IF EXISTS application_versions_receipt_unique;
ALTER TABLE IF EXISTS ONLY public.application_versions DROP CONSTRAINT IF EXISTS application_versions_pkey;
ALTER TABLE IF EXISTS ONLY public.academic_requirements DROP CONSTRAINT IF EXISTS academic_requirements_pkey;
ALTER TABLE IF EXISTS ONLY public.academic_requirements DROP CONSTRAINT IF EXISTS academic_requirements_cohort_id_specialty_id_unique;
ALTER TABLE IF EXISTS public.users ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.swap_requests ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.specialties ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.shifts ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.shift_batches ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.scenario_studies ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.reports ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.places ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.optimization_runs ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.migrations ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.jobs ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.incidents ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.hospitals ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.failed_jobs ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.cycles ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.cohorts ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.clinical_records ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.availabilities ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.audit_logs ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.assignments ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.assignment_history ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.applications ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.application_versions ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.academic_requirements ALTER COLUMN id DROP DEFAULT;
DROP SEQUENCE IF EXISTS public.users_id_seq;
DROP TABLE IF EXISTS public.users;
DROP SEQUENCE IF EXISTS public.swap_requests_id_seq;
DROP TABLE IF EXISTS public.swap_requests;
DROP SEQUENCE IF EXISTS public.specialties_id_seq;
DROP TABLE IF EXISTS public.specialties;
DROP SEQUENCE IF EXISTS public.shifts_id_seq;
DROP TABLE IF EXISTS public.shifts;
DROP SEQUENCE IF EXISTS public.shift_batches_id_seq;
DROP TABLE IF EXISTS public.shift_batches;
DROP TABLE IF EXISTS public.settings;
DROP TABLE IF EXISTS public.sessions;
DROP SEQUENCE IF EXISTS public.scenario_studies_id_seq;
DROP TABLE IF EXISTS public.scenario_studies;
DROP SEQUENCE IF EXISTS public.reports_id_seq;
DROP TABLE IF EXISTS public.reports;
DROP SEQUENCE IF EXISTS public.places_id_seq;
DROP TABLE IF EXISTS public.places;
DROP TABLE IF EXISTS public.password_reset_tokens;
DROP TABLE IF EXISTS public.otp_limits;
DROP TABLE IF EXISTS public.otp_challenges;
DROP SEQUENCE IF EXISTS public.optimization_runs_id_seq;
DROP TABLE IF EXISTS public.optimization_runs;
DROP TABLE IF EXISTS public.notifications;
DROP SEQUENCE IF EXISTS public.migrations_id_seq;
DROP TABLE IF EXISTS public.migrations;
DROP SEQUENCE IF EXISTS public.jobs_id_seq;
DROP TABLE IF EXISTS public.jobs;
DROP TABLE IF EXISTS public.job_batches;
DROP SEQUENCE IF EXISTS public.incidents_id_seq;
DROP TABLE IF EXISTS public.incidents;
DROP SEQUENCE IF EXISTS public.hospitals_id_seq;
DROP TABLE IF EXISTS public.hospitals;
DROP SEQUENCE IF EXISTS public.failed_jobs_id_seq;
DROP TABLE IF EXISTS public.failed_jobs;
DROP SEQUENCE IF EXISTS public.cycles_id_seq;
DROP TABLE IF EXISTS public.cycles;
DROP SEQUENCE IF EXISTS public.cohorts_id_seq;
DROP TABLE IF EXISTS public.cohorts;
DROP SEQUENCE IF EXISTS public.clinical_records_id_seq;
DROP TABLE IF EXISTS public.clinical_records;
DROP TABLE IF EXISTS public.cache_locks;
DROP TABLE IF EXISTS public.cache;
DROP SEQUENCE IF EXISTS public.availabilities_id_seq;
DROP TABLE IF EXISTS public.availabilities;
DROP SEQUENCE IF EXISTS public.audit_logs_id_seq;
DROP TABLE IF EXISTS public.audit_logs;
DROP SEQUENCE IF EXISTS public.assignments_id_seq;
DROP TABLE IF EXISTS public.assignments;
DROP SEQUENCE IF EXISTS public.assignment_history_id_seq;
DROP TABLE IF EXISTS public.assignment_history;
DROP SEQUENCE IF EXISTS public.applications_id_seq;
DROP TABLE IF EXISTS public.applications;
DROP SEQUENCE IF EXISTS public.application_versions_id_seq;
DROP TABLE IF EXISTS public.application_versions;
DROP SEQUENCE IF EXISTS public.academic_requirements_id_seq;
DROP TABLE IF EXISTS public.academic_requirements;
SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: academic_requirements; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.academic_requirements (
    id bigint NOT NULL,
    cohort_id bigint NOT NULL,
    specialty_id bigint NOT NULL,
    min_days integer NOT NULL,
    required_blocks integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.academic_requirements OWNER TO unifranz_user;

--
-- Name: academic_requirements_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.academic_requirements_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.academic_requirements_id_seq OWNER TO unifranz_user;

--
-- Name: academic_requirements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.academic_requirements_id_seq OWNED BY public.academic_requirements.id;


--
-- Name: application_versions; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.application_versions (
    id bigint NOT NULL,
    application_id bigint NOT NULL,
    preferences json NOT NULL,
    receipt uuid NOT NULL,
    changed_by bigint NOT NULL,
    reason text NOT NULL,
    created_at timestamp(0) without time zone NOT NULL
);


ALTER TABLE public.application_versions OWNER TO unifranz_user;

--
-- Name: application_versions_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.application_versions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.application_versions_id_seq OWNER TO unifranz_user;

--
-- Name: application_versions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.application_versions_id_seq OWNED BY public.application_versions.id;


--
-- Name: applications; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.applications (
    id bigint NOT NULL,
    cycle_id bigint NOT NULL,
    user_id bigint NOT NULL,
    preferences json NOT NULL,
    receipt uuid NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.applications OWNER TO unifranz_user;

--
-- Name: applications_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.applications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.applications_id_seq OWNER TO unifranz_user;

--
-- Name: applications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.applications_id_seq OWNED BY public.applications.id;


--
-- Name: assignment_history; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.assignment_history (
    id bigint NOT NULL,
    assignment_id bigint NOT NULL,
    run_id bigint NOT NULL,
    place_id bigint NOT NULL,
    tutor_id bigint NOT NULL,
    changed_by bigint NOT NULL,
    reason character varying(255) NOT NULL,
    created_at timestamp(0) without time zone NOT NULL
);


ALTER TABLE public.assignment_history OWNER TO unifranz_user;

--
-- Name: assignment_history_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.assignment_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.assignment_history_id_seq OWNER TO unifranz_user;

--
-- Name: assignment_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.assignment_history_id_seq OWNED BY public.assignment_history.id;


--
-- Name: assignments; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.assignments (
    id bigint NOT NULL,
    cycle_id bigint NOT NULL,
    user_id bigint NOT NULL,
    place_id bigint NOT NULL,
    tutor_id bigint NOT NULL,
    run_id bigint NOT NULL,
    status character varying(255) DEFAULT 'aprobada'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.assignments OWNER TO unifranz_user;

--
-- Name: assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.assignments_id_seq OWNER TO unifranz_user;

--
-- Name: assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.assignments_id_seq OWNED BY public.assignments.id;


--
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.audit_logs (
    id bigint NOT NULL,
    user_id bigint,
    action character varying(255) NOT NULL,
    details json NOT NULL,
    created_at timestamp(0) without time zone NOT NULL
);


ALTER TABLE public.audit_logs OWNER TO unifranz_user;

--
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.audit_logs_id_seq OWNER TO unifranz_user;

--
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- Name: availabilities; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.availabilities (
    id bigint NOT NULL,
    tutor_id bigint NOT NULL,
    starts_at timestamp(0) without time zone NOT NULL,
    ends_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT availability_dates_valid CHECK ((ends_at > starts_at))
);


ALTER TABLE public.availabilities OWNER TO unifranz_user;

--
-- Name: availabilities_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.availabilities_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.availabilities_id_seq OWNER TO unifranz_user;

--
-- Name: availabilities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.availabilities_id_seq OWNED BY public.availabilities.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache OWNER TO unifranz_user;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO unifranz_user;

--
-- Name: clinical_records; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.clinical_records (
    id bigint NOT NULL,
    assignment_id bigint NOT NULL,
    date date NOT NULL,
    attendance character varying(255) NOT NULL,
    grade integer,
    notes text,
    recorded_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    competencies json,
    rubric_snapshot json,
    CONSTRAINT grade_valid CHECK (((grade IS NULL) OR ((grade >= 0) AND (grade <= 100))))
);


ALTER TABLE public.clinical_records OWNER TO unifranz_user;

--
-- Name: clinical_records_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.clinical_records_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.clinical_records_id_seq OWNER TO unifranz_user;

--
-- Name: clinical_records_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.clinical_records_id_seq OWNED BY public.clinical_records.id;


--
-- Name: cohorts; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.cohorts (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    entry_year integer NOT NULL,
    active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.cohorts OWNER TO unifranz_user;

--
-- Name: cohorts_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.cohorts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.cohorts_id_seq OWNER TO unifranz_user;

--
-- Name: cohorts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.cohorts_id_seq OWNED BY public.cohorts.id;


--
-- Name: cycles; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.cycles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    specialty_id bigint NOT NULL,
    starts_on date NOT NULL,
    ends_on date NOT NULL,
    opens_at timestamp(0) without time zone NOT NULL,
    closes_at timestamp(0) without time zone NOT NULL,
    status character varying(255) DEFAULT 'borrador'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    cohort_id bigint,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT cycles_dates_valid CHECK (((ends_on >= starts_on) AND (closes_at > opens_at)))
);


ALTER TABLE public.cycles OWNER TO unifranz_user;

--
-- Name: cycles_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.cycles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.cycles_id_seq OWNER TO unifranz_user;

--
-- Name: cycles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.cycles_id_seq OWNED BY public.cycles.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO unifranz_user;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO unifranz_user;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: hospitals; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.hospitals (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    agreement_until date NOT NULL,
    active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.hospitals OWNER TO unifranz_user;

--
-- Name: hospitals_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.hospitals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.hospitals_id_seq OWNER TO unifranz_user;

--
-- Name: hospitals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.hospitals_id_seq OWNED BY public.hospitals.id;


--
-- Name: incidents; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.incidents (
    id bigint NOT NULL,
    assignment_id bigint NOT NULL,
    created_by bigint NOT NULL,
    type character varying(255) NOT NULL,
    description text NOT NULL,
    status character varying(255) DEFAULT 'abierta'::character varying NOT NULL,
    resolution text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.incidents OWNER TO unifranz_user;

--
-- Name: incidents_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.incidents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.incidents_id_seq OWNER TO unifranz_user;

--
-- Name: incidents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.incidents_id_seq OWNED BY public.incidents.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO unifranz_user;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO unifranz_user;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO unifranz_user;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO unifranz_user;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO unifranz_user;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    data text NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.notifications OWNER TO unifranz_user;

--
-- Name: optimization_runs; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.optimization_runs (
    id bigint NOT NULL,
    cycle_id bigint NOT NULL,
    created_by bigint NOT NULL,
    input json NOT NULL,
    result json NOT NULL,
    input_hash character varying(64) NOT NULL,
    status character varying(255) DEFAULT 'propuesta'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    execution_status character varying(255) DEFAULT 'completada'::character varying NOT NULL,
    plan_status character varying(255) DEFAULT 'calculado'::character varying NOT NULL,
    request_id uuid,
    started_at timestamp(0) without time zone,
    finished_at timestamp(0) without time zone,
    duration_ms integer,
    diagnostic text,
    attempts integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.optimization_runs OWNER TO unifranz_user;

--
-- Name: optimization_runs_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.optimization_runs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.optimization_runs_id_seq OWNER TO unifranz_user;

--
-- Name: optimization_runs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.optimization_runs_id_seq OWNED BY public.optimization_runs.id;


--
-- Name: otp_challenges; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.otp_challenges (
    id character varying(64) NOT NULL,
    user_id bigint,
    account_hash character varying(64) NOT NULL,
    purpose character varying(20) NOT NULL,
    verifier character varying(64) NOT NULL,
    attempts integer DEFAULT 0 NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    revoked_at timestamp(0) without time zone,
    verified_at timestamp(0) without time zone,
    consumed_at timestamp(0) without time zone,
    sent_at timestamp(0) without time zone,
    grant_verifier character varying(64),
    grant_expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.otp_challenges OWNER TO unifranz_user;

--
-- Name: otp_limits; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.otp_limits (
    key character varying(64) NOT NULL,
    count integer DEFAULT 0 NOT NULL,
    window_start timestamp(0) without time zone NOT NULL,
    last_request_at timestamp(0) without time zone
);


ALTER TABLE public.otp_limits OWNER TO unifranz_user;

--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO unifranz_user;

--
-- Name: places; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.places (
    id bigint NOT NULL,
    cycle_id bigint NOT NULL,
    hospital_id bigint NOT NULL,
    tutor_id bigint NOT NULL,
    capacity integer NOT NULL,
    reserve integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT places_capacity_valid CHECK (((capacity >= 0) AND (reserve >= 0) AND (reserve <= capacity)))
);


ALTER TABLE public.places OWNER TO unifranz_user;

--
-- Name: places_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.places_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.places_id_seq OWNER TO unifranz_user;

--
-- Name: places_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.places_id_seq OWNED BY public.places.id;


--
-- Name: reports; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.reports (
    id bigint NOT NULL,
    cycle_id bigint NOT NULL,
    token uuid NOT NULL,
    snapshot json NOT NULL,
    sha256 character varying(64) NOT NULL,
    path character varying(255) NOT NULL,
    created_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    run_id bigint,
    user_id bigint,
    status character varying(255) DEFAULT 'vigente'::character varying NOT NULL
);


ALTER TABLE public.reports OWNER TO unifranz_user;

--
-- Name: reports_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.reports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reports_id_seq OWNER TO unifranz_user;

--
-- Name: reports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.reports_id_seq OWNED BY public.reports.id;


--
-- Name: scenario_studies; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.scenario_studies (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    matrix json NOT NULL,
    result json NOT NULL,
    alpha numeric(4,3) NOT NULL,
    chosen character varying(255),
    justification text,
    created_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    started_at timestamp(0) without time zone,
    execution_status character varying(255) DEFAULT 'completada'::character varying NOT NULL,
    input json,
    diagnostic text
);


ALTER TABLE public.scenario_studies OWNER TO unifranz_user;

--
-- Name: scenario_studies_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.scenario_studies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.scenario_studies_id_seq OWNER TO unifranz_user;

--
-- Name: scenario_studies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.scenario_studies_id_seq OWNED BY public.scenario_studies.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO unifranz_user;

--
-- Name: settings; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.settings (
    key character varying(255) NOT NULL,
    value json NOT NULL,
    updated_at timestamp(0) without time zone NOT NULL
);


ALTER TABLE public.settings OWNER TO unifranz_user;

--
-- Name: shift_batches; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.shift_batches (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    rest_hours integer NOT NULL,
    status character varying(255) DEFAULT 'propuesta'::character varying NOT NULL,
    created_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    started_at timestamp(0) without time zone,
    execution_status character varying(255) DEFAULT 'completada'::character varying NOT NULL,
    input json,
    input_hash character varying(64),
    diagnostic json
);


ALTER TABLE public.shift_batches OWNER TO unifranz_user;

--
-- Name: shift_batches_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.shift_batches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.shift_batches_id_seq OWNER TO unifranz_user;

--
-- Name: shift_batches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.shift_batches_id_seq OWNED BY public.shift_batches.id;


--
-- Name: shifts; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.shifts (
    id bigint NOT NULL,
    batch_id bigint NOT NULL,
    assignment_id bigint NOT NULL,
    tutor_id bigint,
    starts_at timestamp(0) without time zone NOT NULL,
    ends_at timestamp(0) without time zone NOT NULL,
    performed boolean DEFAULT false NOT NULL,
    reason text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT shift_dates_valid CHECK ((ends_at > starts_at))
);


ALTER TABLE public.shifts OWNER TO unifranz_user;

--
-- Name: shifts_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.shifts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.shifts_id_seq OWNER TO unifranz_user;

--
-- Name: shifts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.shifts_id_seq OWNED BY public.shifts.id;


--
-- Name: specialties; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.specialties (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.specialties OWNER TO unifranz_user;

--
-- Name: specialties_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.specialties_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.specialties_id_seq OWNER TO unifranz_user;

--
-- Name: specialties_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.specialties_id_seq OWNED BY public.specialties.id;


--
-- Name: swap_requests; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.swap_requests (
    id bigint NOT NULL,
    source_id bigint NOT NULL,
    target_id bigint NOT NULL,
    source_place_id bigint NOT NULL,
    target_place_id bigint NOT NULL,
    reason text NOT NULL,
    status character varying(255) DEFAULT 'solicitada'::character varying NOT NULL,
    consented_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.swap_requests OWNER TO unifranz_user;

--
-- Name: swap_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.swap_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.swap_requests_id_seq OWNER TO unifranz_user;

--
-- Name: swap_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.swap_requests_id_seq OWNED BY public.swap_requests.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: unifranz_user
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    role character varying(255) DEFAULT 'interno'::character varying NOT NULL,
    active boolean DEFAULT true NOT NULL,
    eligible boolean DEFAULT false NOT NULL,
    matricula character varying(255),
    merit integer DEFAULT 0 NOT NULL,
    academic_average numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    activated_at timestamp(0) without time zone,
    security_version integer DEFAULT 1 NOT NULL,
    phone character varying(40),
    supervision_limit integer DEFAULT 10 NOT NULL,
    cohort_id bigint,
    deleted_at timestamp(0) without time zone,
    access_approved_at timestamp(0) without time zone,
    access_approved_by bigint,
    CONSTRAINT academic_values_valid CHECK ((((academic_average >= (0)::numeric) AND (academic_average <= (100)::numeric)) AND ((merit >= 0) AND (merit <= 100)) AND (supervision_limit > 0))),
    CONSTRAINT users_role_valid CHECK (((role)::text = ANY ((ARRAY['administrador'::character varying, 'coordinador'::character varying, 'interno'::character varying, 'tutor'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO unifranz_user;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: unifranz_user
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO unifranz_user;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: unifranz_user
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: academic_requirements id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.academic_requirements ALTER COLUMN id SET DEFAULT nextval('public.academic_requirements_id_seq'::regclass);


--
-- Name: application_versions id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.application_versions ALTER COLUMN id SET DEFAULT nextval('public.application_versions_id_seq'::regclass);


--
-- Name: applications id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.applications ALTER COLUMN id SET DEFAULT nextval('public.applications_id_seq'::regclass);


--
-- Name: assignment_history id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignment_history ALTER COLUMN id SET DEFAULT nextval('public.assignment_history_id_seq'::regclass);


--
-- Name: assignments id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignments ALTER COLUMN id SET DEFAULT nextval('public.assignments_id_seq'::regclass);


--
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- Name: availabilities id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.availabilities ALTER COLUMN id SET DEFAULT nextval('public.availabilities_id_seq'::regclass);


--
-- Name: clinical_records id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.clinical_records ALTER COLUMN id SET DEFAULT nextval('public.clinical_records_id_seq'::regclass);


--
-- Name: cohorts id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.cohorts ALTER COLUMN id SET DEFAULT nextval('public.cohorts_id_seq'::regclass);


--
-- Name: cycles id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.cycles ALTER COLUMN id SET DEFAULT nextval('public.cycles_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: hospitals id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.hospitals ALTER COLUMN id SET DEFAULT nextval('public.hospitals_id_seq'::regclass);


--
-- Name: incidents id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.incidents ALTER COLUMN id SET DEFAULT nextval('public.incidents_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: optimization_runs id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.optimization_runs ALTER COLUMN id SET DEFAULT nextval('public.optimization_runs_id_seq'::regclass);


--
-- Name: places id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.places ALTER COLUMN id SET DEFAULT nextval('public.places_id_seq'::regclass);


--
-- Name: reports id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.reports ALTER COLUMN id SET DEFAULT nextval('public.reports_id_seq'::regclass);


--
-- Name: scenario_studies id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.scenario_studies ALTER COLUMN id SET DEFAULT nextval('public.scenario_studies_id_seq'::regclass);


--
-- Name: shift_batches id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.shift_batches ALTER COLUMN id SET DEFAULT nextval('public.shift_batches_id_seq'::regclass);


--
-- Name: shifts id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.shifts ALTER COLUMN id SET DEFAULT nextval('public.shifts_id_seq'::regclass);


--
-- Name: specialties id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.specialties ALTER COLUMN id SET DEFAULT nextval('public.specialties_id_seq'::regclass);


--
-- Name: swap_requests id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.swap_requests ALTER COLUMN id SET DEFAULT nextval('public.swap_requests_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: academic_requirements; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.academic_requirements (id, cohort_id, specialty_id, min_days, required_blocks, created_at, updated_at, deleted_at) FROM stdin;
1	1	2	5	6	2026-11-18 17:48:51	2026-11-18 17:48:51	\N
\.


--
-- Data for Name: application_versions; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.application_versions (id, application_id, preferences, receipt, changed_by, reason, created_at) FROM stdin;
\.


--
-- Data for Name: applications; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.applications (id, cycle_id, user_id, preferences, receipt, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: assignment_history; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.assignment_history (id, assignment_id, run_id, place_id, tutor_id, changed_by, reason, created_at) FROM stdin;
\.


--
-- Data for Name: assignments; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.assignments (id, cycle_id, user_id, place_id, tutor_id, run_id, status, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: audit_logs; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.audit_logs (id, user_id, action, details, created_at) FROM stdin;
1	1	sesion.inicio	[]	2026-11-16 23:10:15
2	1	sesion.cierre	[]	2026-11-16 23:11:24
3	2	sesion.inicio	[]	2026-11-16 23:11:59
4	\N	sesion.fallida	{"identificador":"6f5e1e02f761c25afdb1f7f1703d72a2d6d98dd500ba22f53a5fe0dff14f0a37"}	2026-11-17 05:47:09
5	\N	sesion.fallida	{"identificador":"6f5e1e02f761c25afdb1f7f1703d72a2d6d98dd500ba22f53a5fe0dff14f0a37"}	2026-11-17 07:52:02
6	\N	sesion.fallida	{"identificador":"e58645239f7dc1c85a4489b51628a5bc6471314c906b06cdfdc4730e90c273c6"}	2026-11-17 07:52:33
7	\N	sesion.fallida	{"identificador":"6f5e1e02f761c25afdb1f7f1703d72a2d6d98dd500ba22f53a5fe0dff14f0a37"}	2026-11-18 07:04:07
8	2	sesion.inicio	[]	2026-11-18 16:54:10
9	2	sesion.cierre	[]	2026-11-18 16:59:03
10	1	sesion.inicio	[]	2026-11-18 17:00:10
11	1	sesion.cierre	[]	2026-11-18 17:00:13
12	1	sesion.inicio	[]	2026-11-18 17:00:33
13	1	sesion.cierre	[]	2026-11-18 17:01:15
14	3	sesion.inicio	[]	2026-11-18 17:01:27
15	3	sesion.cierre	[]	2026-11-18 17:07:39
16	1	sesion.inicio	[]	2026-11-18 17:07:49
17	1	sesion.cierre	[]	2026-11-18 17:12:27
18	7	sesion.inicio	[]	2026-11-18 17:12:34
19	7	sesion.cierre	[]	2026-11-18 17:13:23
20	2	sesion.inicio	[]	2026-11-18 17:13:41
21	2	sesion.cierre	[]	2026-11-18 17:14:13
22	3	sesion.inicio	[]	2026-11-18 17:14:23
23	3	sesion.cierre	[]	2026-11-18 17:14:32
24	3	sesion.inicio	[]	2026-11-18 17:25:31
25	3	sesion.cierre	[]	2026-11-18 17:26:43
26	7	sesion.inicio	[]	2026-11-18 17:26:54
27	7	sesion.cierre	[]	2026-11-18 17:27:11
28	1	sesion.inicio	[]	2026-11-18 17:28:10
29	1	sesion.cierre	[]	2026-11-18 17:29:35
30	2	sesion.inicio	[]	2026-11-18 17:29:40
31	2	sesion.cierre	[]	2026-11-18 17:30:18
32	1	sesion.inicio	[]	2026-11-18 17:30:25
33	1	clave.definida_por_administrador	{"usuario":8}	2026-11-18 17:31:28
34	1	gestion.guardada	{"recurso":"usuarios","id":8}	2026-11-18 17:31:28
35	1	sesion.cierre	[]	2026-11-18 17:31:35
36	2	sesion.inicio	[]	2026-11-18 17:31:41
37	2	gestion.guardada	{"recurso":"cohortes","id":1}	2026-11-18 17:48:12
38	2	gestion.guardada	{"recurso":"cohortes","id":2}	2026-11-18 17:48:23
39	2	gestion.guardada	{"recurso":"requisitos","id":1}	2026-11-18 17:48:51
40	2	gestion.guardada	{"recurso":"hospitales","id":3}	2026-11-18 17:50:37
41	2	gestion.guardada	{"recurso":"hospitales","id":2}	2026-11-18 17:52:08
42	2	gestion.guardada	{"recurso":"hospitales","id":1}	2026-11-18 17:52:27
43	2	gestion.guardada	{"recurso":"hospitales","id":1}	2026-11-18 17:52:41
44	2	cuenta.habilitada	{"usuario":8,"rol":"interno"}	2026-11-18 17:55:30
45	2	gestion.guardada	{"recurso":"internos","id":8}	2026-11-18 17:55:30
46	2	sesion.cierre	[]	2026-11-18 18:05:12
47	\N	sesion.fallida	{"identificador":"9861b9ab72e7e9c9f5d5beec6668de4a936ce9dd882600364b52b08e2295a180"}	2026-11-18 18:05:35
48	1	sesion.inicio	[]	2026-11-18 18:05:43
49	1	sesion.cierre	[]	2026-11-18 18:05:55
50	8	sesion.inicio	[]	2026-11-18 18:06:07
51	8	sesion.cierre	[]	2026-11-18 18:58:34
52	1	sesion.inicio	[]	2026-11-18 18:58:56
53	2	sesion.inicio	[]	2026-11-19 11:59:56
54	2	sesion.cierre	[]	2026-11-19 12:00:59
55	1	sesion.inicio	[]	2026-11-19 12:17:18
56	1	clave.definida_por_administrador	{"usuario":9}	2026-11-19 12:18:11
57	1	gestion.guardada	{"recurso":"usuarios","id":9}	2026-11-19 12:18:11
58	1	sesion.cierre	[]	2026-11-19 12:18:14
59	2	sesion.inicio	[]	2026-11-19 12:18:20
60	2	cuenta.habilitada	{"usuario":9,"rol":"tutor"}	2026-11-19 12:18:48
61	2	gestion.guardada	{"recurso":"habilitaciones","id":9}	2026-11-19 12:18:48
62	2	cuenta.habilitada	{"usuario":9,"rol":"tutor"}	2026-11-19 12:19:03
63	2	gestion.guardada	{"recurso":"habilitaciones","id":9}	2026-11-19 12:19:03
64	2	sesion.cierre	[]	2026-11-19 12:19:11
65	1	sesion.inicio	[]	2026-11-19 12:19:19
66	1	gestion.guardada	{"recurso":"usuarios","id":9}	2026-11-19 12:19:38
67	1	sesion.cierre	[]	2026-11-19 12:19:41
68	9	sesion.inicio	[]	2026-11-19 12:19:50
69	1	sesion.inicio	[]	2026-11-19 15:50:15
70	\N	otp.solicitado	{"proposito":"recovery"}	2026-11-20 13:37:08
71	\N	otp.solicitado	{"proposito":"activation"}	2026-11-20 13:37:50
\.


--
-- Data for Name: availabilities; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.availabilities (id, tutor_id, starts_at, ends_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.cache (key, value, expiration) FROM stdin;
siclin-cache-login:9861b9ab72e7e9c9f5d5beec6668de4a936ce9dd882600364b52b08e2295a180:timer	i:1795039835;	1795039835
siclin-cache-login:9861b9ab72e7e9c9f5d5beec6668de4a936ce9dd882600364b52b08e2295a180	i:1;	1795039835
siclin-cache-412a22b0c80fea47c7a2ea2dda96bab6357db455:timer	i:1794999907;	1794999907
siclin-cache-412a22b0c80fea47c7a2ea2dda96bab6357db455	i:1;	1794999907
siclin-cache-login:412a22b0c80fea47c7a2ea2dda96bab6357db455:timer	i:1795117875;	1795117875
siclin-cache-login:412a22b0c80fea47c7a2ea2dda96bab6357db455	i:1;	1795117875
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: clinical_records; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.clinical_records (id, assignment_id, date, attendance, grade, notes, recorded_by, created_at, updated_at, competencies, rubric_snapshot) FROM stdin;
\.


--
-- Data for Name: cohorts; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.cohorts (id, name, entry_year, active, created_at, updated_at, deleted_at) FROM stdin;
1	Luis Jimenez	2026	t	2026-11-18 17:48:12	2026-11-18 17:48:12	\N
2	Camila Rojas	2026	t	2026-11-18 17:48:23	2026-11-18 17:48:23	\N
\.


--
-- Data for Name: cycles; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.cycles (id, name, specialty_id, starts_on, ends_on, opens_at, closes_at, status, created_at, updated_at, cohort_id, deleted_at) FROM stdin;
1	Convocatoria · Medicina Interna	1	2026-11-23	2026-12-23	2026-11-15 18:44:24	2026-11-21 18:44:24	abierta	2026-11-16 18:44:24	2026-11-16 18:44:24	\N	\N
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: hospitals; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.hospitals (id, name, agreement_until, active, created_at, updated_at, deleted_at) FROM stdin;
3	Hospital Agramont	2029-10-12	t	2026-11-18 17:50:37	2026-11-18 17:50:37	\N
2	Hospital Los Pinos	2027-11-16	t	2026-11-16 18:44:24	2026-11-18 17:52:08	\N
1	Hospital Municipal La Paz	2031-05-30	t	2026-11-16 18:44:24	2026-11-18 17:52:41	\N
\.


--
-- Data for Name: incidents; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.incidents (id, assignment_id, created_by, type, description, status, resolution, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_11_14_033641_create_siclin_tables	1
5	2026_11_14_040000_add_swaps_and_rubrics	1
6	2026_11_14_050000_create_notifications_table	1
7	2026_11_14_060000_add_academic_average_to_users	1
8	2026_11_16_000001_add_security_and_execution_state	1
9	2026_11_16_183007_add_cohorts_requirements_and_application_versions	1
10	2026_11_18_062345_add_access_approval_and_soft_deletes	2
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.notifications (id, type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: optimization_runs; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.optimization_runs (id, cycle_id, created_by, input, result, input_hash, status, created_at, updated_at, execution_status, plan_status, request_id, started_at, finished_at, duration_ms, diagnostic, attempts) FROM stdin;
\.


--
-- Data for Name: otp_challenges; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.otp_challenges (id, user_id, account_hash, purpose, verifier, attempts, expires_at, revoked_at, verified_at, consumed_at, sent_at, grant_verifier, grant_expires_at, created_at, updated_at) FROM stdin;
6prUYYK8CwRek39seedvIo6kDWrGwErcOnrCeIohYnSFBaaRQfabKhB1fHw0mOw7	3	382de80533b84385fab55650515f1942d6f42051a1eb170695318e7a9d63ee9b	recovery	142a7bd4a8d83b4e5d3b9b354738a80a9a5122b40608c5fef1baccc0beff28d9	0	2026-11-20 13:47:08	\N	\N	\N	2026-11-20 13:37:09	\N	\N	2026-11-20 13:37:08	2026-11-20 13:37:08
zUCcbHzo4eWUHPgBZ3XOHNYluBjghyWCTyAQM6Agmamglbyttet5vDsbKIjm2X1F	\N	0aa4e1d1962864a398221b97ea7ceefcd4e750852a5521f4ada11150813fd380	activation	2802351dbe88f4526f9d47b6c95e19cf8aea1640069200e160a6f807d14d581a	0	2026-11-20 13:47:50	\N	\N	\N	\N	\N	\N	2026-11-20 13:37:50	2026-11-20 13:37:50
\.


--
-- Data for Name: otp_limits; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.otp_limits (key, count, window_start, last_request_at) FROM stdin;
382de80533b84385fab55650515f1942d6f42051a1eb170695318e7a9d63ee9b	1	2026-11-20 13:37:08	2026-11-20 13:37:08
fa314f465b5b81cbfbb74b5fc4529deb703d4e6ebe52fa52589075fb6a6f5cfe	2	2026-11-20 13:37:08	2026-11-20 13:37:50
0aa4e1d1962864a398221b97ea7ceefcd4e750852a5521f4ada11150813fd380	1	2026-11-20 13:37:50	2026-11-20 13:37:50
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: places; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.places (id, cycle_id, hospital_id, tutor_id, capacity, reserve, created_at, updated_at, deleted_at) FROM stdin;
1	1	1	3	3	1	2026-11-16 18:44:24	2026-11-16 18:44:24	\N
2	1	2	4	3	1	2026-11-16 18:44:24	2026-11-16 18:44:24	\N
\.


--
-- Data for Name: reports; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.reports (id, cycle_id, token, snapshot, sha256, path, created_by, created_at, updated_at, run_id, user_id, status) FROM stdin;
\.


--
-- Data for Name: scenario_studies; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.scenario_studies (id, name, matrix, result, alpha, chosen, justification, created_by, created_at, updated_at, started_at, execution_status, input, diagnostic) FROM stdin;
1	Ejemplo didáctico · valores hipotéticos	{"A1":[90,60,20],"A2":[75,65,40],"A3":[80,70,60]}	{"orientation":"benefits","conversion":"utilidad = beneficio","scenarios":[0,1,2],"utility":{"A1":[90,60,20],"A2":[75,65,40],"A3":[80,70,60]},"scores":{"A1":{"maximin":20,"maximax":90,"laplace":56.666666666666664,"hurwicz":55,"savage":40},"A2":{"maximin":40,"maximax":75,"laplace":60,"hurwicz":57.5,"savage":20},"A3":{"maximin":60,"maximax":80,"laplace":70,"hurwicz":70,"savage":10}},"regrets":{"A1":[0,10,40],"A2":[15,5,20],"A3":[10,0,0]},"winners":{"maximin":["A3"],"maximax":["A1"],"laplace":["A3"],"hurwicz":["A3"],"savage":["A3"]},"sensitivity":[{"alpha":0,"alternativas":["A3"],"values":{"A1":20,"A2":40,"A3":60}},{"alpha":0.1,"alternativas":["A3"],"values":{"A1":27,"A2":43.5,"A3":62}},{"alpha":0.2,"alternativas":["A3"],"values":{"A1":34,"A2":47,"A3":64}},{"alpha":0.3,"alternativas":["A3"],"values":{"A1":41,"A2":50.5,"A3":66}},{"alpha":0.4,"alternativas":["A3"],"values":{"A1":48,"A2":54,"A3":68}},{"alpha":0.5,"alternativas":["A3"],"values":{"A1":55,"A2":57.5,"A3":70}},{"alpha":0.6,"alternativas":["A3"],"values":{"A1":62,"A2":61,"A3":72}},{"alpha":0.7,"alternativas":["A3"],"values":{"A1":69,"A2":64.5,"A3":74}},{"alpha":0.8,"alternativas":["A1","A3"],"values":{"A1":76,"A2":68,"A3":76}},{"alpha":0.9,"alternativas":["A1"],"values":{"A1":83,"A2":71.5,"A3":78}},{"alpha":1,"alternativas":["A1"],"values":{"A1":90,"A2":75,"A3":80}}]}	0.500	\N	\N	2	2026-11-16 18:44:24	2026-11-16 18:44:24	\N	completada	\N	\N
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
6rtGfNCE9d2pgtrespTltlC8iO4MYjdXRm26T0LI	9	172.20.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36	ZXlKcGRpSTZJbkJYWVZscE5XTjRkaTlYYkRKTmFIcHlVWFZwVG1jOVBTSXNJblpoYkhWbElqb2lRMDh4WWxwS1UyWklUMDV6V0N0WVJ6ZEVSamRxZEZrNWMwNTFlVEJ1ZVhoaVMwUnFkRlpEVFVkb1dFNDJRM1phZEVkaVVtcFRiVTF0Um5OMk1rTXlWbWswYUROUVNYSXhXa0ZOUzBSQ2JIcGxNM3ByVm5KeGRrOUVaak5GU1c5VmRsRmhlV0ZYUzBWVlNrWjZXRGRvTW5SalEyRlZjbHBRT0ZZd1JYbzFSVzl0TldwTGNIbEpkMHBPUkhwYWRreHJTbUpDY0dOdWFXZHRTV1puZUZKeldteFJlbXdyV0U5UmRtUjZVMVpJY1VKeVNqbDRUU3RRVG1KaGJVeFFkemxYU2xSRFpURlBjWFZ4UWxwaWQwNXJkV1p5ZDFSUWRYRmFUVEJPVEhGT0t6ZzJWV05HYjBWdlZHUlRhbFJOVGxVMWFYWk9UeTlpTUhGd09FWnVOV293T0ZCWFUwazFiRVZ5Y1ZGaVpHbEJNRUV5ZURsSllVcHFLMGQ2ZDJSblFTdEVXVkpTY0VaVWVrZzRNVzVMUm5VMVdHOWxlVXhrWlZKdk5HcFhNVk5aTjJvM1dITkNibWwxTkZOWk5TdE5LMU54V25Od1ptZzFTbTEyUlRKcFlrWktOakZETmxCNlJYUkdSRmh4WVRsWWMwcDVTRUo1ZEVKSFF6UllhSGxtVkVZMlJtUXZXVk5TVFZWNGJrVlpjMDlUZDFsaFQwVTFaa3dyWjFoTGRVcHNSRU5RTWxwQ2FsYzRValp2U0hOR2JtSmhiMHRMWnpGaVpuTkpSbk4wTlRsWlJpSXNJbTFoWXlJNkltWXlPV1psTmpZM09XVXdNVGcxTnpnNVlUVXpNekE1WlRGbE5HSTRNVEUwTnpaaU9XRXlORFkyTldReE9USmtaak0yWkRjd05tTXlPR001Wmpaa05HWWlMQ0owWVdjaU9pSWlmUT09	1795108656
f4y6xljPSEQvaGNmysWiaQdc1v7zD42t80FkOjBC	1	172.20.0.1	Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Mobile Safari/537.36	ZXlKcGRpSTZJbGhLV25kVVJUbE5ZVUZpV200NUwzaGpMMEpvU0VFOVBTSXNJblpoYkhWbElqb2lSakZCWW5GVlUxRXZWVWhyUzFrelQySnpVVWwwTDBocGFVOHdVSEIwV0V4d2JtbzNSblo0V1VKQmJsQkROR3haVlVZeUszTndWbkJhWW5KaWJXbHJjRmhvY21FNGNHY3ZWSEZrVFhjeVZWaEJZbG8zTUZWTFZGZGFXVmhETlRWMmN6bGxkbTEzUlhsM1N6VnNhM2RCWVdwM09EaGthRzlYVEN0S1MyWllWbTFKT0d3M01sUTNhM1ZpTjJKSUwyNUxUblpaUVdoUVpsRjNNV0ZrTDBaeFEyaHBNV3hhYjJsVVR6TkxkRWRTU1dkM2REazJXRlowV0dwdE1WTkxiMWxPTUZKM1kybFlXbTlsYTFsMk5UbDZUbW96ZUU5NE1qQkRXREJTTUVZM2FUbEZlV2hRV0V0TFRqSk9aRk5wYUdkMlVscG1OQ3N2VEVkTVN5OWhSRWxzT0ZwdWJFaFFVemsxUlVWaWRTOXFTWEE1WjBOd1JGYzFibGcxUkROdE0ybzFhMU55Y0VGSGFWQlpabnBEWjNaTVVHWTNiekp3WkZaVFRscGhVbFZEUzNoUWVWbDNVRXBoVUhZMmVIQkNVM0pqWlhoWFQxbExZM2R5YWpCT05XUkJhbVZ0ZFVSeFJFeERlV3hVZFhwMVkwWk1SSGRpV2xOVVozVk5LMFJ1WkhsWWVYUTRWSHBuV0VVelRGTXdXRlowVEhwc1EwVlZZMlZDUjAxaVJrVkhRa1JuY21adVRsaEdXbTVvUmxOblZFVnNkMEpzWW5jd2IyeFBlbFE0VjFSMlRqRTNNRnBEYkM5aFIwUmFka1kxVkc5SVF6QlpUMEU5UFNJc0ltMWhZeUk2SW1KaU9UWmpNV0V3T1RJNU9UUXdabUUxWkRBNU5UQTVZalpqWVRabFpqSTRaVEkwWWpOaE9ERTFOMlZoWXpaaE1HRXlNek00T0RkaE1ERXhOak00WW1JaUxDSjBZV2NpT2lJaWZRPT0=	1795117827
gwLNrGsxw5t7jD6YxzqSc9vwXyhaK55xEpSekLde	\N	172.20.0.1	Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36	ZXlKcGRpSTZJbEpwZUZkMVVYbEpjbXhTWVZwNk5XMTJUMkpRYTBFOVBTSXNJblpoYkhWbElqb2liakprV0dsNVUyRkxVbll3VXpsYWFXRlZaMU00WWtvME1tSTNkRkExYkV3NGRXRktPV2N5WW5veE0xTTNNU3N5VVUxMVQxQmpTMUZHYWpOU1ZYazBibGhNU21neWFGVkJibmRUY0dSMFRXbFBiV1IxYjBGaVJYTldjRVZ0TVROTE9UbGtOaTlFV2xKd1NGbDBSMUowWmpsRE9GbFhkRXg0SzNodk0xUnJiME5QTlRKTlNFNHJTRWRNWTFaM1JVRnpaVW96V1VGUFVtbG1UMjlCTm13M2VqTk1hMGRDZW1jNVRXUnZkbXBVYjNOcFMycERlVGR5Vm5Cd2VXVjZlbTUwT0hNdmVXOVFaSEZpZWxObVVUVkxXbVpOTVdveFUyZHNOUzlJUTNwc1RFWk5kbFJXVUZBM1kyNWFTVmhPYldOdVFYVjZkMWswUW1aSFQyVkNXVmwyVDI5Q1YyRTFjMHhTWmxsU1QyUjZlbUpEZWxkdFltRkhVa1ZzY2l0S2VqazNVVWxYV2pWalEwVllia1pyWTFad1MydEpTek5YV0ZNdlNuYzVaV0phUW5OUlZHWkxObkZpUXl0V2VYZ3lZVEU1V2xGd1NtOWxNVFl6YjFGRGRraEZPRWN3TUdOWFdtWk5TM05oU1ROR04yWjNXamRHU2xOSGFWUTNXR3hVZUVSMVZXeHJRM0JJYmxacVVtUk1jRzU1YUZBdlpHSm1RMncyYTBOcWRVOWhkemhYVmtsTE0ydDVTRGR2UmxWTmNraGtaV2d3VmxKemNYYzFXbHA1TjFjNVdUaDVkRlUyUlVocU9XVlJhRE5OWW05S01uZDNTbWM5UFNJc0ltMWhZeUk2SW1KaVptUm1NRGRsTXpnek1UQTJOelpsTkdOaU1qVmtOR0k0TW1GbU56QmhPVGxqWW1JeE9EWTFOamxoTnpZMU5EVmpZVEJsTmpRNU56Y3lORGt4T1RjaUxDSjBZV2NpT2lJaWZRPT0=	1795196270
\.


--
-- Data for Name: settings; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.settings (key, value, updated_at) FROM stdin;
\.


--
-- Data for Name: shift_batches; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.shift_batches (id, name, rest_hours, status, created_by, created_at, updated_at, started_at, execution_status, input, input_hash, diagnostic) FROM stdin;
\.


--
-- Data for Name: shifts; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.shifts (id, batch_id, assignment_id, tutor_id, starts_at, ends_at, performed, reason, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: specialties; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.specialties (id, name, created_at, updated_at, deleted_at) FROM stdin;
1	Medicina Interna	2026-11-16 18:44:24	2026-11-16 18:44:24	\N
2	Cirugía	2026-11-16 18:44:24	2026-11-16 18:44:24	\N
3	Pediatría	2026-11-16 18:44:24	2026-11-16 18:44:24	\N
4	Ginecología y Obstetricia	2026-11-16 18:44:24	2026-11-16 18:44:24	\N
5	Salud Pública	2026-11-16 18:44:24	2026-11-16 18:44:24	\N
\.


--
-- Data for Name: swap_requests; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.swap_requests (id, source_id, target_id, source_place_id, target_place_id, reason, status, consented_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: unifranz_user
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, role, active, eligible, matricula, merit, academic_average, activated_at, security_version, phone, supervision_limit, cohort_id, deleted_at, access_approved_at, access_approved_by) FROM stdin;
5	Ana López	interno@siclin.test	\N	$2y$12$BhhbuTUcTBs.5F6dMlDiMOk0zn6tS1kd.DrLNHy7MPiAtH.sEq5Jq	\N	2026-11-16 18:44:23	2026-11-18 16:44:55	interno	t	t	INT-4	86	86.00	2026-11-16 18:44:23	1	\N	10	\N	\N	2026-11-18 16:06:25	\N
6	Diego Martínez	interno2@siclin.test	\N	$2y$12$Dw7hegfoF9IxLFDGOGtYuuw49KATo8dFHHs3AT8ZHVpoGh8z2SmIK	\N	2026-11-16 18:44:23	2026-11-18 16:44:56	interno	t	t	INT-5	85	85.00	2026-11-16 18:44:23	1	\N	10	\N	\N	2026-11-18 16:06:25	\N
7	Valeria Choque	interno3@siclin.test	\N	$2y$12$3lLLu6xiQu06EY4Oswd76.SkiJ4HSh7Cco6FWbaRROI3D14DfJKRa	\N	2026-11-16 18:44:24	2026-11-18 16:44:56	interno	t	t	INT-6	84	84.00	2026-11-16 18:44:23	1	\N	10	\N	\N	2026-11-18 16:06:25	\N
8	Luis Jimenez	interno5@siclin.test	\N	$2y$12$cMUJTnsrxzYdwiyKGP9lw.mTCDcQOY1Cpvw8cRRWhuk5MPQFwW8im	\N	2026-11-18 17:31:28	2026-11-18 17:55:30	interno	t	t	INIT-8	91	89.00	2026-11-18 17:31:28	1	77856461	10	1	\N	2026-11-18 17:55:30	2
9	Dr. Alejandro Diaz	tutor3@siclin.test	\N	$2y$12$sGVH5wzTJkaTKTlPzNJGSOWXg5kTpfUojpsClT2wz7XIQkFoAnlPe	\N	2026-11-19 12:18:11	2026-11-19 12:19:38	tutor	t	f	\N	0	0.00	2026-11-19 12:18:11	1	\N	10	\N	\N	2026-11-19 12:18:48	2
1	Administrador SICLIN	admin@siclin.test	\N	$2y$12$N6klk0TncdsCdfAiZxdduO2ZhLVoA5BymXVwUw5yAWD0vWLzBnJsO	\N	2026-11-16 18:44:22	2026-11-18 16:44:55	administrador	t	f	\N	90	90.00	2026-11-16 18:44:22	1	\N	10	\N	\N	2026-11-18 16:06:25	\N
2	Coordinación Académica	coordinador@siclin.test	\N	$2y$12$7r5v2DDtIiDVRJWYt5KHdOzqXbUC2pSwDtRKMBQwMDUkA/o0vZAxG	\N	2026-11-16 18:44:22	2026-11-18 16:44:55	coordinador	t	f	\N	89	89.00	2026-11-16 18:44:22	1	\N	10	\N	\N	2026-11-18 16:06:25	\N
3	Dra. Lucía Fernández	tutor@siclin.test	\N	$2y$12$UHQ.pmWWYun5uw.Xa5TtzesPR.L6GATxyh7D4k2q6PLpRMpmWqOD.	\N	2026-11-16 18:44:23	2026-11-18 16:44:55	tutor	t	f	\N	88	88.00	2026-11-16 18:44:22	1	\N	10	\N	\N	2026-11-18 16:06:25	\N
4	Dr. Mateo Ríos	tutor2@siclin.test	\N	$2y$12$LdU6cnuJo817/KRb6u4MAey89rsyfd4jDaLx3XksLtzKZY0.vojRW	\N	2026-11-16 18:44:23	2026-11-18 16:44:55	tutor	t	f	\N	87	87.00	2026-11-16 18:44:23	1	\N	10	\N	\N	2026-11-18 16:06:25	\N
\.


--
-- Name: academic_requirements_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.academic_requirements_id_seq', 1, true);


--
-- Name: application_versions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.application_versions_id_seq', 1, false);


--
-- Name: applications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.applications_id_seq', 1, false);


--
-- Name: assignment_history_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.assignment_history_id_seq', 1, false);


--
-- Name: assignments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.assignments_id_seq', 1, false);


--
-- Name: audit_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.audit_logs_id_seq', 71, true);


--
-- Name: availabilities_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.availabilities_id_seq', 1, false);


--
-- Name: clinical_records_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.clinical_records_id_seq', 1, false);


--
-- Name: cohorts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.cohorts_id_seq', 2, true);


--
-- Name: cycles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.cycles_id_seq', 1, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: hospitals_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.hospitals_id_seq', 3, true);


--
-- Name: incidents_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.incidents_id_seq', 1, false);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.jobs_id_seq', 2, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.migrations_id_seq', 10, true);


--
-- Name: optimization_runs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.optimization_runs_id_seq', 1, false);


--
-- Name: places_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.places_id_seq', 2, true);


--
-- Name: reports_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.reports_id_seq', 1, false);


--
-- Name: scenario_studies_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.scenario_studies_id_seq', 1, true);


--
-- Name: shift_batches_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.shift_batches_id_seq', 1, false);


--
-- Name: shifts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.shifts_id_seq', 1, false);


--
-- Name: specialties_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.specialties_id_seq', 5, true);


--
-- Name: swap_requests_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.swap_requests_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: unifranz_user
--

SELECT pg_catalog.setval('public.users_id_seq', 9, true);


--
-- Name: academic_requirements academic_requirements_cohort_id_specialty_id_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.academic_requirements
    ADD CONSTRAINT academic_requirements_cohort_id_specialty_id_unique UNIQUE (cohort_id, specialty_id);


--
-- Name: academic_requirements academic_requirements_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.academic_requirements
    ADD CONSTRAINT academic_requirements_pkey PRIMARY KEY (id);


--
-- Name: application_versions application_versions_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.application_versions
    ADD CONSTRAINT application_versions_pkey PRIMARY KEY (id);


--
-- Name: application_versions application_versions_receipt_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.application_versions
    ADD CONSTRAINT application_versions_receipt_unique UNIQUE (receipt);


--
-- Name: applications applications_cycle_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.applications
    ADD CONSTRAINT applications_cycle_id_user_id_unique UNIQUE (cycle_id, user_id);


--
-- Name: applications applications_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.applications
    ADD CONSTRAINT applications_pkey PRIMARY KEY (id);


--
-- Name: applications applications_receipt_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.applications
    ADD CONSTRAINT applications_receipt_unique UNIQUE (receipt);


--
-- Name: assignment_history assignment_history_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignment_history
    ADD CONSTRAINT assignment_history_pkey PRIMARY KEY (id);


--
-- Name: assignments assignments_cycle_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignments
    ADD CONSTRAINT assignments_cycle_id_user_id_unique UNIQUE (cycle_id, user_id);


--
-- Name: assignments assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignments
    ADD CONSTRAINT assignments_pkey PRIMARY KEY (id);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: availabilities availabilities_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.availabilities
    ADD CONSTRAINT availabilities_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: clinical_records clinical_records_assignment_id_date_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.clinical_records
    ADD CONSTRAINT clinical_records_assignment_id_date_unique UNIQUE (assignment_id, date);


--
-- Name: clinical_records clinical_records_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.clinical_records
    ADD CONSTRAINT clinical_records_pkey PRIMARY KEY (id);


--
-- Name: cohorts cohorts_name_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.cohorts
    ADD CONSTRAINT cohorts_name_unique UNIQUE (name);


--
-- Name: cohorts cohorts_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.cohorts
    ADD CONSTRAINT cohorts_pkey PRIMARY KEY (id);


--
-- Name: cycles cycles_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.cycles
    ADD CONSTRAINT cycles_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: hospitals hospitals_name_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.hospitals
    ADD CONSTRAINT hospitals_name_unique UNIQUE (name);


--
-- Name: hospitals hospitals_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.hospitals
    ADD CONSTRAINT hospitals_pkey PRIMARY KEY (id);


--
-- Name: incidents incidents_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.incidents
    ADD CONSTRAINT incidents_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: optimization_runs optimization_runs_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.optimization_runs
    ADD CONSTRAINT optimization_runs_pkey PRIMARY KEY (id);


--
-- Name: optimization_runs optimization_runs_request_id_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.optimization_runs
    ADD CONSTRAINT optimization_runs_request_id_unique UNIQUE (request_id);


--
-- Name: otp_challenges otp_challenges_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.otp_challenges
    ADD CONSTRAINT otp_challenges_pkey PRIMARY KEY (id);


--
-- Name: otp_limits otp_limits_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.otp_limits
    ADD CONSTRAINT otp_limits_pkey PRIMARY KEY (key);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: places places_cycle_id_hospital_id_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.places
    ADD CONSTRAINT places_cycle_id_hospital_id_unique UNIQUE (cycle_id, hospital_id);


--
-- Name: places places_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.places
    ADD CONSTRAINT places_pkey PRIMARY KEY (id);


--
-- Name: reports reports_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_pkey PRIMARY KEY (id);


--
-- Name: reports reports_token_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_token_unique UNIQUE (token);


--
-- Name: scenario_studies scenario_studies_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.scenario_studies
    ADD CONSTRAINT scenario_studies_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: settings settings_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (key);


--
-- Name: shift_batches shift_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.shift_batches
    ADD CONSTRAINT shift_batches_pkey PRIMARY KEY (id);


--
-- Name: shifts shifts_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.shifts
    ADD CONSTRAINT shifts_pkey PRIMARY KEY (id);


--
-- Name: specialties specialties_name_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.specialties
    ADD CONSTRAINT specialties_name_unique UNIQUE (name);


--
-- Name: specialties specialties_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.specialties
    ADD CONSTRAINT specialties_pkey PRIMARY KEY (id);


--
-- Name: swap_requests swap_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.swap_requests
    ADD CONSTRAINT swap_requests_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_matricula_unique; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_matricula_unique UNIQUE (matricula);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: unifranz_user
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: unifranz_user
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: unifranz_user
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: unifranz_user
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: unifranz_user
--

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


--
-- Name: otp_challenges_account_hash_index; Type: INDEX; Schema: public; Owner: unifranz_user
--

CREATE INDEX otp_challenges_account_hash_index ON public.otp_challenges USING btree (account_hash);


--
-- Name: otp_challenges_expires_at_index; Type: INDEX; Schema: public; Owner: unifranz_user
--

CREATE INDEX otp_challenges_expires_at_index ON public.otp_challenges USING btree (expires_at);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: unifranz_user
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: unifranz_user
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: users_email_normalized; Type: INDEX; Schema: public; Owner: unifranz_user
--

CREATE UNIQUE INDEX users_email_normalized ON public.users USING btree (lower(TRIM(BOTH FROM email)));


--
-- Name: academic_requirements academic_requirements_cohort_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.academic_requirements
    ADD CONSTRAINT academic_requirements_cohort_id_foreign FOREIGN KEY (cohort_id) REFERENCES public.cohorts(id);


--
-- Name: academic_requirements academic_requirements_specialty_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.academic_requirements
    ADD CONSTRAINT academic_requirements_specialty_id_foreign FOREIGN KEY (specialty_id) REFERENCES public.specialties(id);


--
-- Name: application_versions application_versions_application_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.application_versions
    ADD CONSTRAINT application_versions_application_id_foreign FOREIGN KEY (application_id) REFERENCES public.applications(id);


--
-- Name: application_versions application_versions_changed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.application_versions
    ADD CONSTRAINT application_versions_changed_by_foreign FOREIGN KEY (changed_by) REFERENCES public.users(id);


--
-- Name: applications applications_cycle_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.applications
    ADD CONSTRAINT applications_cycle_id_foreign FOREIGN KEY (cycle_id) REFERENCES public.cycles(id);


--
-- Name: applications applications_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.applications
    ADD CONSTRAINT applications_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: assignment_history assignment_history_assignment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignment_history
    ADD CONSTRAINT assignment_history_assignment_id_foreign FOREIGN KEY (assignment_id) REFERENCES public.assignments(id);


--
-- Name: assignment_history assignment_history_changed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignment_history
    ADD CONSTRAINT assignment_history_changed_by_foreign FOREIGN KEY (changed_by) REFERENCES public.users(id);


--
-- Name: assignment_history assignment_history_place_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignment_history
    ADD CONSTRAINT assignment_history_place_id_foreign FOREIGN KEY (place_id) REFERENCES public.places(id);


--
-- Name: assignment_history assignment_history_run_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignment_history
    ADD CONSTRAINT assignment_history_run_id_foreign FOREIGN KEY (run_id) REFERENCES public.optimization_runs(id);


--
-- Name: assignment_history assignment_history_tutor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignment_history
    ADD CONSTRAINT assignment_history_tutor_id_foreign FOREIGN KEY (tutor_id) REFERENCES public.users(id);


--
-- Name: assignments assignments_cycle_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignments
    ADD CONSTRAINT assignments_cycle_id_foreign FOREIGN KEY (cycle_id) REFERENCES public.cycles(id);


--
-- Name: assignments assignments_place_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignments
    ADD CONSTRAINT assignments_place_id_foreign FOREIGN KEY (place_id) REFERENCES public.places(id);


--
-- Name: assignments assignments_run_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignments
    ADD CONSTRAINT assignments_run_id_foreign FOREIGN KEY (run_id) REFERENCES public.optimization_runs(id);


--
-- Name: assignments assignments_tutor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignments
    ADD CONSTRAINT assignments_tutor_id_foreign FOREIGN KEY (tutor_id) REFERENCES public.users(id);


--
-- Name: assignments assignments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.assignments
    ADD CONSTRAINT assignments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: audit_logs audit_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: availabilities availabilities_tutor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.availabilities
    ADD CONSTRAINT availabilities_tutor_id_foreign FOREIGN KEY (tutor_id) REFERENCES public.users(id);


--
-- Name: clinical_records clinical_records_assignment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.clinical_records
    ADD CONSTRAINT clinical_records_assignment_id_foreign FOREIGN KEY (assignment_id) REFERENCES public.assignments(id);


--
-- Name: clinical_records clinical_records_recorded_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.clinical_records
    ADD CONSTRAINT clinical_records_recorded_by_foreign FOREIGN KEY (recorded_by) REFERENCES public.users(id);


--
-- Name: cycles cycles_cohort_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.cycles
    ADD CONSTRAINT cycles_cohort_id_foreign FOREIGN KEY (cohort_id) REFERENCES public.cohorts(id);


--
-- Name: cycles cycles_specialty_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.cycles
    ADD CONSTRAINT cycles_specialty_id_foreign FOREIGN KEY (specialty_id) REFERENCES public.specialties(id);


--
-- Name: incidents incidents_assignment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.incidents
    ADD CONSTRAINT incidents_assignment_id_foreign FOREIGN KEY (assignment_id) REFERENCES public.assignments(id);


--
-- Name: incidents incidents_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.incidents
    ADD CONSTRAINT incidents_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: optimization_runs optimization_runs_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.optimization_runs
    ADD CONSTRAINT optimization_runs_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: optimization_runs optimization_runs_cycle_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.optimization_runs
    ADD CONSTRAINT optimization_runs_cycle_id_foreign FOREIGN KEY (cycle_id) REFERENCES public.cycles(id);


--
-- Name: otp_challenges otp_challenges_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.otp_challenges
    ADD CONSTRAINT otp_challenges_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: places places_cycle_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.places
    ADD CONSTRAINT places_cycle_id_foreign FOREIGN KEY (cycle_id) REFERENCES public.cycles(id);


--
-- Name: places places_hospital_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.places
    ADD CONSTRAINT places_hospital_id_foreign FOREIGN KEY (hospital_id) REFERENCES public.hospitals(id);


--
-- Name: places places_tutor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.places
    ADD CONSTRAINT places_tutor_id_foreign FOREIGN KEY (tutor_id) REFERENCES public.users(id);


--
-- Name: reports reports_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: reports reports_cycle_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_cycle_id_foreign FOREIGN KEY (cycle_id) REFERENCES public.cycles(id);


--
-- Name: reports reports_run_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_run_id_foreign FOREIGN KEY (run_id) REFERENCES public.optimization_runs(id);


--
-- Name: reports reports_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.reports
    ADD CONSTRAINT reports_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: scenario_studies scenario_studies_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.scenario_studies
    ADD CONSTRAINT scenario_studies_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: shift_batches shift_batches_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.shift_batches
    ADD CONSTRAINT shift_batches_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: shifts shifts_assignment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.shifts
    ADD CONSTRAINT shifts_assignment_id_foreign FOREIGN KEY (assignment_id) REFERENCES public.assignments(id);


--
-- Name: shifts shifts_batch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.shifts
    ADD CONSTRAINT shifts_batch_id_foreign FOREIGN KEY (batch_id) REFERENCES public.shift_batches(id);


--
-- Name: shifts shifts_tutor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.shifts
    ADD CONSTRAINT shifts_tutor_id_foreign FOREIGN KEY (tutor_id) REFERENCES public.users(id);


--
-- Name: swap_requests swap_requests_source_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.swap_requests
    ADD CONSTRAINT swap_requests_source_id_foreign FOREIGN KEY (source_id) REFERENCES public.assignments(id);


--
-- Name: swap_requests swap_requests_source_place_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.swap_requests
    ADD CONSTRAINT swap_requests_source_place_id_foreign FOREIGN KEY (source_place_id) REFERENCES public.places(id);


--
-- Name: swap_requests swap_requests_target_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.swap_requests
    ADD CONSTRAINT swap_requests_target_id_foreign FOREIGN KEY (target_id) REFERENCES public.assignments(id);


--
-- Name: swap_requests swap_requests_target_place_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.swap_requests
    ADD CONSTRAINT swap_requests_target_place_id_foreign FOREIGN KEY (target_place_id) REFERENCES public.places(id);


--
-- Name: users users_access_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_access_approved_by_foreign FOREIGN KEY (access_approved_by) REFERENCES public.users(id);


--
-- Name: users users_cohort_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: unifranz_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_cohort_id_foreign FOREIGN KEY (cohort_id) REFERENCES public.cohorts(id);


--
-- PostgreSQL database dump complete
--

\unrestrict 9YAh3iGvevMUVGfGtozLbkGvZRgQHJTwOxwlq8FghfzNF3VS02NpRar1wmkaoi9

