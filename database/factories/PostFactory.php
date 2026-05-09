<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    private static array $titles = [
        'vulnerabilities' => [
            'CVE-2024-3094: The XZ Utils Supply Chain Backdoor — Full Technical Breakdown',
            'Buffer Overflow Exploitation on Modern Linux: Bypassing ASLR and Stack Canaries',
            'SQL Injection to RCE: Leveraging pg_sleep and COPY TO/FROM for Shell Access',
            'Log4Shell (CVE-2021-44228): Still Exploitable Three Years Later?',
            'Prototype Pollution in Node.js: From Property Injection to Remote Code Execution',
            'Heap Use-After-Free in Chrome V8: A Step-by-Step Exploitation Analysis',
            'SSRF in AWS Environments: Stealing IAM Credentials via IMDSv1',
            'Java Deserialization Attacks: Gadget Chains and Detection Strategies',
            'HTTP/2 Rapid Reset (CVE-2023-44487): Technical Root Cause Analysis',
            'WebSocket Cross-Site Hijacking: CSRF Beyond the HTTP Layer',
            'Path Traversal to LFI: Chaining Directory Traversal with Log Poisoning',
            'XXE Injection: Out-of-Band Exploitation and Blind Data Exfiltration',
            'Insecure Deserialization in PHP: Exploiting POP Chains via __wakeup',
            'OAuth 2.0 Token Hijacking: Misconfigurations That Lead to Account Takeover',
            'Mass Assignment Vulnerabilities in Laravel and Rails Applications',
        ],
        'analysis' => [
            'Reverse Engineering a Commercial Ransomware Sample with Ghidra',
            'Inside Emotet: C2 Infrastructure Analysis and Protocol Dissection',
            'Memory Forensics: Recovering AES Keys from Hibernation Files',
            'APT28 TTP Analysis: From Spearphishing Email to Domain Persistence',
            'Decompiling Stripped Go Binaries: Symbol Recovery and Function Naming',
            'Static vs Dynamic Analysis: Choosing the Right Approach for Malware Triage',
            'Threat Hunting with Sigma Rules: Detecting Lateral Movement in Windows Logs',
            'Analyzing Cobalt Strike Beacon Configurations: Extraction and Attribution',
            'Unmasking AsyncRAT: Behavioral Analysis in a Controlled Sandbox',
            'MalDoc Analysis: Extracting Shellcode from Weaponized Office Documents',
            'Lateral Movement in Active Directory: A Red Team Case Study',
            'Dissecting a Phishing Kit: Infrastructure, Evasion, and Attribution',
        ],
        'tools' => [
            'Building a Custom C2 Framework with Python and DNS Tunneling',
            'Burp Suite Extensions Every Web Pentester Should Know',
            'Nuclei Templates: Automating Vulnerability Discovery at Scale',
            'Frida for iOS Security Testing: Runtime Hooking and SSL Unpinning',
            'Ghidra vs IDA Pro: A Practical Feature Comparison for Reverse Engineers',
            'Writing Custom Nmap NSE Scripts for Targeted Service Enumeration',
            'BloodHound and SharpHound: Mapping Attack Paths in Active Directory',
            'Volatility3 for Memory Forensics: A Practical Plugin Reference',
            'Ligolo-ng: Seamless Network Pivoting Without SOCKS Proxies',
            'CyberChef for Security Analysts: 10 Recipes You Should Know',
            'Semgrep for Secure Code Review: Writing Custom Rules',
            'Caido vs Burp Suite: A Modern Web Proxy Comparison',
        ],
        'good-practices' => [
            'Zero Trust Architecture: Implementation Beyond the Marketing Buzzword',
            'Secrets Management in CI/CD Pipelines: Avoiding Credential Leaks',
            'Hardening Docker Containers: From Privileged to Minimal Attack Surface',
            'Dependency Review: Automating SCA in Your GitHub Actions Pipeline',
            'Secure Coding in PHP: OWASP Top 10 Checklist for Developers',
            'Incident Response Playbook: The First 24 Hours After a Breach',
            'Cryptographic Agility: Preparing Your Codebase for Post-Quantum Algorithms',
            'WAF Tuning in Production: Reducing False Positives Without Lowering Guard',
            'Security Headers Deep Dive: CSP, HSTS, and Permissions-Policy Explained',
            'Threat Modeling with STRIDE: A Practical Guide for Engineering Teams',
            'Secure API Design: Authentication, Rate Limiting, and Input Validation',
            'Supply Chain Security: Verifying Integrity from Code to Production',
        ],
    ];

    public function definition(): array
    {
        $tag = $this->faker->randomElement(['vulnerabilities', 'analysis', 'tools', 'good-practices']);

        return [
            'title'   => $this->faker->randomElement(self::$titles[$tag]),
            'content' => implode("\n\n", $this->faker->paragraphs(rand(5, 10))),
            'tags'    => $tag,
            'user_id' => rand(1, 51),
        ];
    }
}
